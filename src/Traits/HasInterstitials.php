<?php

declare(strict_types=1);

namespace effina\Larastitial\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use effina\Larastitial\Models\InterstitialResponse;
use effina\Larastitial\Models\InterstitialView;

/**
 * Trait to add to your User model for interstitial tracking helpers.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasInterstitials
{
    /**
     * Get all interstitial views for this user.
     */
    public function interstitialViews(): HasMany
    {
        return $this->hasMany(InterstitialView::class, 'user_id');
    }

    /**
     * Get all interstitial responses for this user.
     */
    public function interstitialResponses(): HasMany
    {
        return $this->hasMany(InterstitialResponse::class, 'user_id');
    }

    /**
     * Check if the user has viewed a specific interstitial.
     */
    public function hasViewedInterstitial(int|string $interstitialId): bool
    {
        return $this->interstitialViews()
            ->where('interstitial_id', $interstitialId)
            ->exists();
    }

    /**
     * Check if the user has completed a specific interstitial.
     */
    public function hasCompletedInterstitial(int|string $interstitialId): bool
    {
        return $this->interstitialViews()
            ->where('interstitial_id', $interstitialId)
            ->where('action', 'completed')
            ->exists();
    }

    /**
     * Check if the user has dismissed a specific interstitial.
     */
    public function hasDismissedInterstitial(int|string $interstitialId): bool
    {
        return $this->interstitialViews()
            ->where('interstitial_id', $interstitialId)
            ->whereIn('action', ['dismissed', 'dont_show_again'])
            ->exists();
    }

    /**
     * Get the IDs of all interstitials this user has viewed.
     */
    public function getViewedInterstitialIds(): Collection
    {
        return $this->interstitialViews()
            ->pluck('interstitial_id')
            ->unique();
    }

    /**
     * Get the IDs of all interstitials this user has completed.
     */
    public function getCompletedInterstitialIds(): Collection
    {
        return $this->interstitialViews()
            ->where('action', 'completed')
            ->pluck('interstitial_id')
            ->unique();
    }

    /**
     * Get responses for a specific interstitial.
     */
    public function getInterstitialResponses(int|string $interstitialId): Collection
    {
        return $this->interstitialResponses()
            ->where('interstitial_id', $interstitialId)
            ->get();
    }
}
