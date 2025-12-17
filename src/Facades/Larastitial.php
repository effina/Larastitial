<?php

declare(strict_types=1);

namespace effina\Larastitial\Facades;

use Illuminate\Support\Facades\Facade;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Services\InterstitialManager;
use effina\Larastitial\Testing\LarastitialFake;

/**
 * @method static \Illuminate\Support\Collection getApplicable(?\Illuminate\Contracts\Auth\Authenticatable $user, string $context, array $meta = [])
 * @method static ?Interstitial getForEvent(object $event)
 * @method static \Illuminate\Support\Collection getForRoute(string $route)
 * @method static void markViewed(Interstitial $interstitial, ?\Illuminate\Contracts\Auth\Authenticatable $user, string $action)
 * @method static bool shouldShow(Interstitial $interstitial, ?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static void recordResponse(Interstitial $interstitial, ?\Illuminate\Contracts\Auth\Authenticatable $user, array $data)
 * @method static \Illuminate\Support\Collection getQueued(?string $type = null)
 * @method static bool hasQueued(?string $type = null)
 * @method static ?Interstitial find(string $identifier)
 * @method static string renderInline(string $type, string $slot)
 * @method static InterstitialManager forCurrentUser()
 * @method static InterstitialManager modal()
 * @method static InterstitialManager fullPage()
 * @method static InterstitialManager inline()
 *
 * @see \effina\Larastitial\Services\InterstitialManager
 */
class Larastitial extends Facade
{
    /**
     * Replace the bound instance with a fake.
     */
    public static function fake(): LarastitialFake
    {
        static::swap($fake = new LarastitialFake());

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'larastitial';
    }
}
