<?php

declare(strict_types=1);

namespace effina\Larastitial\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use effina\Larastitial\Contracts\ContentRenderer;
use effina\Larastitial\Events\InterstitialCompleted;
use effina\Larastitial\Events\InterstitialDismissed;
use effina\Larastitial\Events\InterstitialResponseSubmitted;
use effina\Larastitial\Events\InterstitialTriggered;
use effina\Larastitial\Events\InterstitialViewed;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Models\InterstitialResponse;
use effina\Larastitial\Support\Enums\InterstitialType;
use effina\Larastitial\Support\Enums\QueueBehavior;
use effina\Larastitial\Support\Enums\ViewAction;

class InterstitialManager
{
    protected ?Authenticatable $user = null;
    protected ?InterstitialType $typeFilter = null;
    protected Collection $queued;

    public function __construct(
        protected AudienceResolver $audienceResolver,
        protected FrequencyChecker $frequencyChecker,
        protected ContentRenderer $contentRenderer
    ) {
        $this->queued = collect();
    }

    /**
     * Set the user context for queries.
     */
    public function forUser(?Authenticatable $user): self
    {
        $clone = clone $this;
        $clone->user = $user;
        return $clone;
    }

    /**
     * Use the currently authenticated user.
     */
    public function forCurrentUser(): self
    {
        return $this->forUser(Auth::user());
    }

    /**
     * Filter by modal type.
     */
    public function modal(): self
    {
        $clone = clone $this;
        $clone->typeFilter = InterstitialType::Modal;
        return $clone;
    }

    /**
     * Filter by full-page type.
     */
    public function fullPage(): self
    {
        $clone = clone $this;
        $clone->typeFilter = InterstitialType::FullPage;
        return $clone;
    }

    /**
     * Filter by inline type.
     */
    public function inline(): self
    {
        $clone = clone $this;
        $clone->typeFilter = InterstitialType::Inline;
        return $clone;
    }

    /**
     * Get all applicable interstitials for a user and context.
     */
    public function getApplicable(
        ?Authenticatable $user,
        string $context,
        array $meta = []
    ): Collection {
        $query = Interstitial::query()
            ->active()
            ->scheduledNow()
            ->byPriority();

        if ($this->typeFilter) {
            $query->ofType($this->typeFilter);
        }

        // Apply tenant scope if enabled
        if (config('larastitial.multi_tenant.enabled')) {
            $resolver = config('larastitial.multi_tenant.resolver');
            $tenantId = $resolver ? app($resolver)->resolve() : null;
            $query->forTenant($tenantId);
        }

        // Apply context-specific filters
        $this->applyContextFilters($query, $context, $meta);

        $interstitials = $query->get();
        $sessionId = session()->getId();

        // Filter by audience and frequency
        return $interstitials->filter(function (Interstitial $interstitial) use ($user, $sessionId) {
            return $this->shouldShow($interstitial, $user, $sessionId);
        })->values();
    }

    /**
     * Get interstitials triggered by a specific event.
     */
    public function getForEvent(object $event): Collection
    {
        $eventClass = get_class($event);
        $user = $this->extractUserFromEvent($event);

        return Interstitial::query()
            ->active()
            ->scheduledNow()
            ->forEvent($eventClass)
            ->byPriority()
            ->get()
            ->filter(fn (Interstitial $i) => $this->shouldShow($i, $user))
            ->values();
    }

    /**
     * Get interstitials for a specific route.
     */
    public function getForRoute(string $route): Collection
    {
        $user = Auth::user();

        return Interstitial::query()
            ->active()
            ->scheduledNow()
            ->whereNotNull('trigger_routes')
            ->byPriority()
            ->get()
            ->filter(fn (Interstitial $i) => $i->matchesRoute($route))
            ->filter(fn (Interstitial $i) => $this->shouldShow($i, $user))
            ->values();
    }

    /**
     * Get interstitials for a named inline slot.
     */
    public function getForSlot(string $slot): Collection
    {
        $user = Auth::user();

        return Interstitial::query()
            ->active()
            ->scheduledNow()
            ->inline()
            ->forSlot($slot)
            ->byPriority()
            ->get()
            ->filter(fn (Interstitial $i) => $this->shouldShow($i, $user))
            ->values();
    }

    /**
     * Determine if an interstitial should be shown.
     */
    public function shouldShow(
        Interstitial $interstitial,
        ?Authenticatable $user = null,
        ?string $sessionId = null
    ): bool {
        $user = $user ?? Auth::user();
        $sessionId = $sessionId ?? session()->getId();

        // Check audience match
        if (!$this->audienceResolver->matches($interstitial, $user)) {
            return false;
        }

        // Check frequency
        if (!$this->frequencyChecker->shouldShow($interstitial, $user, $sessionId)) {
            return false;
        }

        return true;
    }

    /**
     * Find an interstitial by name or UUID.
     */
    public function find(string $identifier): ?Interstitial
    {
        return Interstitial::query()
            ->where('uuid', $identifier)
            ->orWhere('name', $identifier)
            ->first();
    }

    /**
     * Mark an interstitial as viewed.
     */
    public function markViewed(
        Interstitial $interstitial,
        ?Authenticatable $user = null,
        string $action = 'viewed'
    ): void {
        $user = $user ?? Auth::user();
        $sessionId = session()->getId();
        $viewAction = ViewAction::tryFrom($action) ?? ViewAction::Viewed;

        $this->frequencyChecker->recordView($interstitial, $user, $sessionId, $viewAction);

        // Fire appropriate event
        match ($viewAction) {
            ViewAction::Viewed => event(new InterstitialViewed($interstitial, $user, $sessionId)),
            ViewAction::Dismissed => event(new InterstitialDismissed($interstitial, $user, $sessionId)),
            ViewAction::Completed => event(new InterstitialCompleted($interstitial, $user, $sessionId)),
            ViewAction::DontShowAgain => event(new InterstitialDismissed($interstitial, $user, $sessionId)),
        };
    }

    /**
     * Record a form response.
     */
    public function recordResponse(
        Interstitial $interstitial,
        ?Authenticatable $user,
        array $data
    ): ?InterstitialResponse {
        $user = $user ?? Auth::user();
        $sessionId = session()->getId();
        $response = null;

        $formStorage = config('larastitial.form_storage', 'both');

        if (in_array($formStorage, ['database', 'both'])) {
            $response = InterstitialResponse::create([
                'interstitial_id' => $interstitial->id,
                'user_id' => $user?->getAuthIdentifier(),
                'session_id' => $sessionId,
                'data' => $data,
            ]);
        }

        if (in_array($formStorage, ['event', 'both'])) {
            event(new InterstitialResponseSubmitted(
                $interstitial,
                $response ?? new InterstitialResponse([
                    'interstitial_id' => $interstitial->id,
                    'user_id' => $user?->getAuthIdentifier(),
                    'session_id' => $sessionId,
                    'data' => $data,
                ]),
                $user,
                $data
            ));
        }

        // Mark as completed
        $this->markViewed($interstitial, $user, 'completed');

        return $response;
    }

    /**
     * Queue an interstitial for display.
     */
    public function queue(Interstitial $interstitial, string $source = 'manual'): void
    {
        $this->queued->push([
            'interstitial' => $interstitial,
            'source' => $source,
        ]);

        // Fire triggered event
        event(new InterstitialTriggered($interstitial, Auth::user(), $source));
    }

    /**
     * Get queued interstitials.
     */
    public function getQueued(?string $type = null): Collection
    {
        $queued = $this->queued->pluck('interstitial');

        if ($type) {
            $queued = $queued->filter(fn (Interstitial $i) => $i->type->value === $type);
        }

        return $this->applyQueueBehavior($queued);
    }

    /**
     * Check if there are queued interstitials.
     */
    public function hasQueued(?string $type = null): bool
    {
        return $this->getQueued($type)->isNotEmpty();
    }

    /**
     * Render an inline interstitial for a slot.
     */
    public function renderInline(string $type, string $slot): string
    {
        if ($type !== 'inline') {
            return '';
        }

        $interstitials = $this->getForSlot($slot);

        if ($interstitials->isEmpty()) {
            return '';
        }

        $interstitial = $interstitials->first();

        // Mark as viewed
        $this->markViewed($interstitial, Auth::user(), 'viewed');

        return $this->contentRenderer->render($interstitial);
    }

    /**
     * Render an interstitial's content.
     */
    public function render(Interstitial $interstitial, array $data = []): string
    {
        return $this->contentRenderer->render($interstitial, $data);
    }

    /**
     * Apply queue behavior rules to filter/sort interstitials.
     */
    protected function applyQueueBehavior(Collection $interstitials): Collection
    {
        $globalBehavior = config('larastitial.queue_behavior', 'configurable');

        if ($globalBehavior === 'priority') {
            // Only return highest priority
            return $interstitials->take(1);
        }

        if ($globalBehavior === 'all') {
            return $interstitials;
        }

        // Configurable mode - check per-interstitial settings
        $result = collect();
        $hasExclusive = false;

        foreach ($interstitials->sortByDesc('priority') as $interstitial) {
            $behavior = $interstitial->getEffectiveQueueBehavior();

            if ($behavior === QueueBehavior::Exclusive->value) {
                if (!$hasExclusive) {
                    $result->push($interstitial);
                    $hasExclusive = true;
                }
            } elseif (!$hasExclusive) {
                $result->push($interstitial);
            }
        }

        return $result->values();
    }

    /**
     * Apply context-specific filters to the query.
     */
    protected function applyContextFilters($query, string $context, array $meta): void
    {
        match ($context) {
            'page_load' => $query->whereNotNull('trigger_routes'),
            'event' => null, // Event filtering handled separately
            'inline' => $query->inline()->whereNotNull('inline_slot'),
            default => null,
        };

        if (isset($meta['route'])) {
            // Route filtering will be applied in post-processing
        }
    }

    /**
     * Try to extract a user from an event object.
     */
    protected function extractUserFromEvent(object $event): ?Authenticatable
    {
        // Common event properties for user
        if (property_exists($event, 'user') && $event->user instanceof Authenticatable) {
            return $event->user;
        }

        if (method_exists($event, 'getUser')) {
            $user = $event->getUser();
            if ($user instanceof Authenticatable) {
                return $user;
            }
        }

        return Auth::user();
    }
}
