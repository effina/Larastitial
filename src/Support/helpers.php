<?php

declare(strict_types=1);

use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Services\InterstitialManager;

if (!function_exists('larastitial')) {
    /**
     * Get the Larastitial manager instance.
     */
    function larastitial(): InterstitialManager
    {
        return app('larastitial');
    }
}

if (!function_exists('interstitial')) {
    /**
     * Get an interstitial by name or UUID.
     */
    function interstitial(string $identifier): ?Interstitial
    {
        return app('larastitial')->find($identifier);
    }
}

if (!function_exists('has_interstitials')) {
    /**
     * Check if there are any queued interstitials for the current request.
     */
    function has_interstitials(?string $type = null): bool
    {
        return app('larastitial')->hasQueued($type);
    }
}
