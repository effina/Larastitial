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
        $eventClass = get_class($event);

        Log::debug('[Larastitial] Event triggered', ['event' => $eventClass]);

        $interstitials = $this->manager->getForEvent($event);

        Log::debug('[Larastitial] Found interstitials for event', [
            'event' => $eventClass,
            'count' => $interstitials->count(),
            'interstitials' => $interstitials->pluck('name')->toArray(),
        ]);

        foreach ($interstitials as $interstitial) {
            // Persist to session since events often trigger before a redirect
            $this->manager->queue($interstitial, 'event:' . $eventClass, persist: true);
            Log::debug('[Larastitial] Queued interstitial', [
                'name' => $interstitial->name,
                'type' => $interstitial->type->value,
            ]);
        }
    }
}
