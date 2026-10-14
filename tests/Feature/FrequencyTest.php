<?php

declare(strict_types=1);

namespace effina\Larastitial\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Models\InterstitialView;
use effina\Larastitial\Services\FrequencyChecker;
use effina\Larastitial\Support\Enums\Frequency;
use effina\Larastitial\Support\Enums\ViewAction;
use effina\Larastitial\Tests\TestCase;

class FrequencyTest extends TestCase
{
    private function makeInterstitial(array $attributes = []): Interstitial
    {
        return Interstitial::create(array_merge([
            'name' => 'test-' . uniqid(),
            'title' => 'Test',
            'content' => '<p>Hi</p>',
        ], $attributes));
    }

    public function testOnceIsHiddenAfterView(): void
    {
        $user = $this->makeUser();
        $interstitial = $this->makeInterstitial(['frequency' => Frequency::Once]);
        $checker = new FrequencyChecker('database');

        $this->assertTrue($checker->shouldShow($interstitial, $user, 'session-a'));

        $checker->recordView($interstitial, $user, 'session-a');

        $this->assertFalse($checker->shouldShow($interstitial, $user, 'session-b'));
    }

    public function testEveryXDaysRespectsIntervalWithDatabase(): void
    {
        $user = $this->makeUser();
        $interstitial = $this->makeInterstitial([
            'frequency' => Frequency::EveryXDays,
            'frequency_days' => 3,
        ]);
        $checker = new FrequencyChecker('database');

        InterstitialView::create([
            'interstitial_id' => $interstitial->id,
            'user_id' => $user->id,
            'session_id' => 'session-a',
            'action' => ViewAction::Viewed,
            'viewed_at' => now()->subDays(1),
        ]);

        $this->assertFalse($checker->shouldShow($interstitial, $user, 'session-a'));

        $this->travel(3)->days();

        $this->assertTrue($checker->shouldShow($interstitial, $user, 'session-a'));
    }

    public function testEveryXDaysRespectsIntervalWithCache(): void
    {
        $user = $this->makeUser();
        $interstitial = $this->makeInterstitial([
            'frequency' => Frequency::EveryXDays,
            'frequency_days' => 2,
        ]);

        // Mirror Laravel 13's default of refusing to unserialize objects from cache.
        config(['cache.stores.restricted' => [
            'driver' => 'array',
            'serialize' => true,
            'serializable_classes' => false,
        ]]);
        config(['cache.default' => 'restricted']);
        Cache::forgetDriver('restricted');

        $checker = new FrequencyChecker('cache');
        $checker->recordView($interstitial, $user, 'session-a');

        $this->assertFalse($checker->shouldShow($interstitial, $user, 'session-a'));

        $this->travel(2)->days();

        $this->assertTrue($checker->shouldShow($interstitial, $user, 'session-a'));
    }

    public function testViewsWithoutUserOrSessionDoNotMatchEveryone(): void
    {
        $other = $this->makeUser();
        $interstitial = $this->makeInterstitial(['frequency' => Frequency::Once]);
        $checker = new FrequencyChecker('database');

        $checker->recordView($interstitial, $other, 'other-session');

        $this->assertTrue($checker->shouldShow($interstitial, null, null));
    }
}
