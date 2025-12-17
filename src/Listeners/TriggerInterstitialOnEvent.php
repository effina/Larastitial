<?php

declare(strict_types=1);

namespace effina\Larastitial\Listeners;

use Illuminate\Support\Facades\Log;
use effina\Larastitial\Services\InterstitialManager;

class TriggerInterstitialOnEvent
{
    public function __construct(
        protected InterstitialManager $manager
    ) {}

    public function handle(object $event): void
    {
        $interstitials = $this->manager->getForEvent($event);

        foreach ($interstitials as $interstitial) {
            $this->manager->queue($interstitial, 'event:' . get_class($event));
        }
    }
}
