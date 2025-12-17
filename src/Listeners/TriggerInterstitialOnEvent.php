<?php

declare(strict_types=1);

namespace effina\Larastitial\Listeners;

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
            // Persist to session since events often trigger before a redirect
            $this->manager->queue($interstitial, 'event:' . get_class($event), persist: true);
        }
    }
}
