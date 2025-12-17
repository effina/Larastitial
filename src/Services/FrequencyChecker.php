<?php

declare(strict_types=1);

namespace effina\Larastitial\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Models\InterstitialView;
use effina\Larastitial\Support\Enums\Frequency;
use effina\Larastitial\Support\Enums\ViewAction;

class FrequencyChecker
{
    public function __construct(
        protected string $storageMode = 'database',
        protected string $cachePrefix = 'larastitial'
    ) {}

    /**
     * Determine if the interstitial should be shown based on frequency rules.
     */
    public function shouldShow(
        Interstitial $interstitial,
        ?Authenticatable $user,
        ?string $sessionId
    ): bool {
        // Check for "don't show again" first
        if ($this->hasDontShowAgain($interstitial, $user, $sessionId)) {
            return false;
        }

        return match ($interstitial->frequency) {
            Frequency::Always => true,
            Frequency::Once => !$this->hasBeenViewed($interstitial, $user, $sessionId),
            Frequency::OncePerSession => !$this->hasBeenViewedThisSession($interstitial, $sessionId),
            Frequency::EveryXDays => $this->daysSinceLastView($interstitial, $user, $sessionId) >= ($interstitial->frequency_days ?? 1),
        };
    }

    /**
     * Check if the user has opted to not show this interstitial again.
     */
    protected function hasDontShowAgain(
        Interstitial $interstitial,
        ?Authenticatable $user,
        ?string $sessionId
    ): bool {
        if ($this->shouldUseCache()) {
            $cacheKey = $this->getCacheKey('dont_show', $interstitial, $user, $sessionId);
            if (Cache::has($cacheKey)) {
                return true;
            }
        }

        if ($this->shouldUseDatabase()) {
            return InterstitialView::query()
                ->where('interstitial_id', $interstitial->id)
                ->forUserOrSession($user?->getAuthIdentifier(), $sessionId)
                ->dontShowAgain()
                ->exists();
        }

        return false;
    }

    /**
     * Check if the interstitial has ever been viewed by the user.
     */
    protected function hasBeenViewed(
        Interstitial $interstitial,
        ?Authenticatable $user,
        ?string $sessionId
    ): bool {
        if ($this->shouldUseCache()) {
            $cacheKey = $this->getCacheKey('viewed', $interstitial, $user, $sessionId);
            if (Cache::has($cacheKey)) {
                return true;
            }
        }

        if ($this->shouldUseDatabase()) {
            return InterstitialView::query()
                ->where('interstitial_id', $interstitial->id)
                ->forUserOrSession($user?->getAuthIdentifier(), $sessionId)
                ->exists();
        }

        return false;
    }

    /**
     * Check if the interstitial has been viewed this session.
     */
    protected function hasBeenViewedThisSession(
        Interstitial $interstitial,
        ?string $sessionId
    ): bool {
        if (empty($sessionId)) {
            return false;
        }

        // For session-based, always check the session/cache
        $sessionKey = config('larastitial.session.viewed_key', 'larastitial_viewed_this_session');
        $viewedInterstitials = session()->get($sessionKey, []);

        return in_array($interstitial->id, $viewedInterstitials);
    }

    /**
     * Get the number of days since the interstitial was last viewed.
     */
    protected function daysSinceLastView(
        Interstitial $interstitial,
        ?Authenticatable $user,
        ?string $sessionId
    ): int {
        $lastViewedAt = null;

        if ($this->shouldUseCache()) {
            $cacheKey = $this->getCacheKey('last_viewed', $interstitial, $user, $sessionId);
            $lastViewedAt = Cache::get($cacheKey);
        }

        if ($lastViewedAt === null && $this->shouldUseDatabase()) {
            $lastView = InterstitialView::query()
                ->where('interstitial_id', $interstitial->id)
                ->forUserOrSession($user?->getAuthIdentifier(), $sessionId)
                ->orderByDesc('viewed_at')
                ->first();

            $lastViewedAt = $lastView?->viewed_at;
        }

        if ($lastViewedAt === null) {
            return PHP_INT_MAX; // Never viewed, so infinite days ago
        }

        return (int) now()->diffInDays($lastViewedAt);
    }

    /**
     * Record that the interstitial was viewed.
     */
    public function recordView(
        Interstitial $interstitial,
        ?Authenticatable $user,
        ?string $sessionId,
        ViewAction $action = ViewAction::Viewed
    ): void {
        if ($this->shouldUseDatabase()) {
            InterstitialView::create([
                'interstitial_id' => $interstitial->id,
                'user_id' => $user?->getAuthIdentifier(),
                'session_id' => $sessionId,
                'action' => $action,
                'viewed_at' => now(),
            ]);
        }

        if ($this->shouldUseCache()) {
            $ttl = config('larastitial.cache_ttl', 60 * 60 * 24 * 30);

            // Mark as viewed
            $viewedKey = $this->getCacheKey('viewed', $interstitial, $user, $sessionId);
            Cache::put($viewedKey, true, $ttl);

            // Store last viewed timestamp
            $lastViewedKey = $this->getCacheKey('last_viewed', $interstitial, $user, $sessionId);
            Cache::put($lastViewedKey, now(), $ttl);

            // Handle don't show again
            if ($action === ViewAction::DontShowAgain) {
                $dontShowKey = $this->getCacheKey('dont_show', $interstitial, $user, $sessionId);
                Cache::put($dontShowKey, true, $ttl);
            }
        }

        // Track in session for "once per session" frequency
        $sessionKey = config('larastitial.session.viewed_key', 'larastitial_viewed_this_session');
        $viewedInterstitials = session()->get($sessionKey, []);
        if (!in_array($interstitial->id, $viewedInterstitials)) {
            $viewedInterstitials[] = $interstitial->id;
            session()->put($sessionKey, $viewedInterstitials);
        }
    }

    /**
     * Generate a cache key for the interstitial/user combination.
     */
    protected function getCacheKey(
        string $type,
        Interstitial $interstitial,
        ?Authenticatable $user,
        ?string $sessionId
    ): string {
        $identifier = $user?->getAuthIdentifier() ?? $sessionId ?? 'anonymous';

        return sprintf(
            '%s:%s:%d:%s',
            $this->cachePrefix,
            $type,
            $interstitial->id,
            $identifier
        );
    }

    protected function shouldUseCache(): bool
    {
        return in_array($this->storageMode, ['cache', 'both']);
    }

    protected function shouldUseDatabase(): bool
    {
        return in_array($this->storageMode, ['database', 'both']);
    }
}
