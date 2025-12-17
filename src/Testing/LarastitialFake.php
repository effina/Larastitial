<?php

declare(strict_types=1);

namespace effina\Larastitial\Testing;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use effina\Larastitial\Models\Interstitial;

class LarastitialFake
{
    protected Collection $interstitialsToTrigger;
    protected Collection $triggeredInterstitials;
    protected Collection $viewedInterstitials;
    protected Collection $recordedResponses;
    protected bool $shouldTriggerAny = true;

    public function __construct()
    {
        $this->interstitialsToTrigger = collect();
        $this->triggeredInterstitials = collect();
        $this->viewedInterstitials = collect();
        $this->recordedResponses = collect();
    }

    /**
     * Specify that a specific interstitial should trigger.
     */
    public function shouldTrigger(Interstitial $interstitial): self
    {
        $this->interstitialsToTrigger->push($interstitial);
        return $this;
    }

    /**
     * Specify that no interstitials should trigger.
     */
    public function shouldNotTrigger(): self
    {
        $this->shouldTriggerAny = false;
        $this->interstitialsToTrigger = collect();
        return $this;
    }

    /**
     * Get applicable interstitials (fake implementation).
     */
    public function getApplicable(?Authenticatable $user, string $context, array $meta = []): Collection
    {
        if (!$this->shouldTriggerAny) {
            return collect();
        }

        return $this->interstitialsToTrigger;
    }

    /**
     * Get interstitials for event (fake implementation).
     */
    public function getForEvent(object $event): Collection
    {
        if (!$this->shouldTriggerAny) {
            return collect();
        }

        return $this->interstitialsToTrigger->filter(
            fn (Interstitial $i) => $i->trigger_event === get_class($event)
        );
    }

    /**
     * Get interstitials for route (fake implementation).
     */
    public function getForRoute(string $route): Collection
    {
        if (!$this->shouldTriggerAny) {
            return collect();
        }

        return $this->interstitialsToTrigger->filter(
            fn (Interstitial $i) => $i->matchesRoute($route)
        );
    }

    /**
     * Check if interstitial should show (fake implementation).
     */
    public function shouldShow(Interstitial $interstitial, ?Authenticatable $user = null): bool
    {
        return $this->shouldTriggerAny && $this->interstitialsToTrigger->contains($interstitial);
    }

    /**
     * Mark an interstitial as viewed (fake implementation).
     */
    public function markViewed(Interstitial $interstitial, ?Authenticatable $user, string $action = 'viewed'): void
    {
        $this->viewedInterstitials->push([
            'interstitial' => $interstitial,
            'user' => $user,
            'action' => $action,
            'timestamp' => now(),
        ]);
    }

    /**
     * Record a response (fake implementation).
     */
    public function recordResponse(Interstitial $interstitial, ?Authenticatable $user, array $data): void
    {
        $this->recordedResponses->push([
            'interstitial' => $interstitial,
            'user' => $user,
            'data' => $data,
            'timestamp' => now(),
        ]);
    }

    /**
     * Queue an interstitial (fake implementation).
     */
    public function queue(Interstitial $interstitial, string $source = 'manual'): void
    {
        $this->triggeredInterstitials->push([
            'interstitial' => $interstitial,
            'source' => $source,
        ]);
    }

    /**
     * Get queued interstitials (fake implementation).
     */
    public function getQueued(?string $type = null): Collection
    {
        $queued = $this->triggeredInterstitials->pluck('interstitial');

        if ($type) {
            $queued = $queued->filter(fn (Interstitial $i) => $i->type->value === $type);
        }

        return $queued;
    }

    /**
     * Check if there are queued interstitials (fake implementation).
     */
    public function hasQueued(?string $type = null): bool
    {
        return $this->getQueued($type)->isNotEmpty();
    }

    /**
     * Find an interstitial (fake implementation).
     */
    public function find(string $identifier): ?Interstitial
    {
        return Interstitial::where('uuid', $identifier)
            ->orWhere('name', $identifier)
            ->first();
    }

    /**
     * Render content (fake implementation).
     */
    public function render(Interstitial $interstitial, array $data = []): string
    {
        return $interstitial->content ?? '';
    }

    /**
     * Render inline (fake implementation).
     */
    public function renderInline(string $type, string $slot): string
    {
        return '';
    }

    /**
     * Assert that a specific interstitial was triggered.
     */
    public function assertTriggered(string $name): void
    {
        $triggered = $this->triggeredInterstitials->pluck('interstitial')
            ->filter(fn (Interstitial $i) => $i->name === $name);

        Assert::assertTrue(
            $triggered->isNotEmpty(),
            "Expected interstitial '{$name}' was not triggered."
        );
    }

    /**
     * Assert that a specific interstitial was not triggered.
     */
    public function assertNotTriggered(string $name): void
    {
        $triggered = $this->triggeredInterstitials->pluck('interstitial')
            ->filter(fn (Interstitial $i) => $i->name === $name);

        Assert::assertTrue(
            $triggered->isEmpty(),
            "Interstitial '{$name}' was unexpectedly triggered."
        );
    }

    /**
     * Assert that an interstitial was viewed by a user.
     */
    public function assertViewedBy(?Authenticatable $user, string $name): void
    {
        $userId = $user?->getAuthIdentifier();

        $viewed = $this->viewedInterstitials->filter(function ($record) use ($name, $userId) {
            $recordUserId = $record['user']?->getAuthIdentifier();
            return $record['interstitial']->name === $name && $recordUserId === $userId;
        });

        Assert::assertTrue(
            $viewed->isNotEmpty(),
            "Expected interstitial '{$name}' was not viewed by the specified user."
        );
    }

    /**
     * Assert that a response was recorded.
     */
    public function assertResponseRecorded(string $name, array $expectedData = []): void
    {
        $responses = $this->recordedResponses->filter(
            fn ($record) => $record['interstitial']->name === $name
        );

        Assert::assertTrue(
            $responses->isNotEmpty(),
            "No response was recorded for interstitial '{$name}'."
        );

        if (!empty($expectedData)) {
            $hasMatchingData = $responses->contains(function ($record) use ($expectedData) {
                foreach ($expectedData as $key => $value) {
                    if (($record['data'][$key] ?? null) !== $value) {
                        return false;
                    }
                }
                return true;
            });

            Assert::assertTrue(
                $hasMatchingData,
                "Response for interstitial '{$name}' did not contain expected data."
            );
        }
    }

    /**
     * Assert no interstitials were triggered.
     */
    public function assertNothingTriggered(): void
    {
        Assert::assertTrue(
            $this->triggeredInterstitials->isEmpty(),
            'Interstitials were triggered when none were expected.'
        );
    }

    /**
     * Get all triggered interstitials (for inspection).
     */
    public function getTriggered(): Collection
    {
        return $this->triggeredInterstitials;
    }

    /**
     * Get all viewed records (for inspection).
     */
    public function getViewed(): Collection
    {
        return $this->viewedInterstitials;
    }

    /**
     * Get all recorded responses (for inspection).
     */
    public function getResponses(): Collection
    {
        return $this->recordedResponses;
    }

    /**
     * Reset the fake state.
     */
    public function reset(): self
    {
        $this->interstitialsToTrigger = collect();
        $this->triggeredInterstitials = collect();
        $this->viewedInterstitials = collect();
        $this->recordedResponses = collect();
        $this->shouldTriggerAny = true;

        return $this;
    }

    // Fluent builder methods for chaining

    public function forCurrentUser(): self
    {
        return $this;
    }

    public function forUser(?Authenticatable $user): self
    {
        return $this;
    }

    public function modal(): self
    {
        return $this;
    }

    public function fullPage(): self
    {
        return $this;
    }

    public function inline(): self
    {
        return $this;
    }
}
