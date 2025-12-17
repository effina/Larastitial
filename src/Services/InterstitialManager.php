<?php

declare(strict_types=1);

namespace effina\Larastitial\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
    protected bool $sessionLoaded = false;

    public function __construct(
        protected AudienceResolver $audienceResolver,
        protected FrequencyChecker $frequencyChecker,
        protected ContentRenderer $contentRenderer
    ) {
        $this->queued = collect();
    }

    /**
     * Get the session key for storing queued interstitials.
     */
    protected function getSessionKey(): string
    {
        return config('larastitial.session.queued_key', 'larastitial_queued');
    }

    /**
     * Load queued interstitials from session.
     */
    public function loadFromSession(): self
    {
        if ($this->sessionLoaded) {
            return $this;
        }

        $sessionKey = $this->getSessionKey();
        $sessionData = session()->get($sessionKey, []);

        Log::debug('[Larastitial] Loading from session', [
            'session_key' => $sessionKey,
            'session_data' => $sessionData,
        ]);

        foreach ($sessionData as $item) {
            if (isset($item['interstitial_id'])) {
                $interstitial = Interstitial::find($item['interstitial_id']);
                if ($interstitial) {
                    $this->queued->push([
                        'interstitial' => $interstitial,
                        'source' => $item['source'] ?? 'session',
                    ]);
                    Log::debug('[Larastitial] Loaded interstitial from session', [
                        'id' => $interstitial->id,
                        'name' => $interstitial->name,
                    ]);
                }
            }
        }

        // Clear the session after loading
        session()->forget($sessionKey);

        $this->sessionLoaded = true;

        return $this;
    }

    /**
     * Save queued interstitials to session (for cross-request persistence).
     */
    protected function saveToSession(): void
    {
        $sessionKey = $this->getSessionKey();
        $sessionData = $this->queued->map(fn ($item) => [
            'interstitial_id' => $item['interstitial']->id,
            'source' => $item['source'],
        ])->toArray();

        Log::debug('[Larastitial] Saving to session', [
            'session_key' => $sessionKey,
            'session_data' => $sessionData,
        ]);

        session()->put($sessionKey, $sessionData);
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

        // Debug: Check what's in the database
        $allWithEvent = Interstitial::query()
            ->where('trigger_event', $eventClass)
            ->get();

        Log::debug('[Larastitial] Checking event interstitials', [
            'event_class' => $eventClass,
            'all_with_event' => $allWithEvent->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'trigger_event' => $i->trigger_event,
                'is_active' => $i->is_active,
                'schedule_start' => $i->trigger_schedule_start,
                'schedule_end' => $i->trigger_schedule_end,
            ])->toArray(),
        ]);

        $afterActive = Interstitial::query()
            ->active()
            ->forEvent($eventClass)
            ->get();

        Log::debug('[Larastitial] After active filter', [
            'count' => $afterActive->count(),
        ]);

        $afterSchedule = Interstitial::query()
            ->active()
            ->scheduledNow()
            ->forEvent($eventClass)
            ->get();

        Log::debug('[Larastitial] After schedule filter', [
            'count' => $afterSchedule->count(),
        ]);

        $final = $afterSchedule
            ->filter(fn (Interstitial $i) => $this->shouldShow($i, $user))
            ->values();

        Log::debug('[Larastitial] After shouldShow filter', [
            'count' => $final->count(),
            'user_id' => $user?->getAuthIdentifier(),
        ]);

        return $final;
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
     *
     * @param bool $persist Whether to persist to session (for cross-request like events)
     */
    public function queue(Interstitial $interstitial, string $source = 'manual', bool $persist = false): void
    {
        $this->queued->push([
            'interstitial' => $interstitial,
            'source' => $source,
        ]);

        // Persist to session if requested (e.g., for event triggers that redirect)
        if ($persist) {
            $this->saveToSession();
        }

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
