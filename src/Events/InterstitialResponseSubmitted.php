<?php

declare(strict_types=1);

namespace effina\Larastitial\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Models\InterstitialResponse;

class InterstitialResponseSubmitted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Interstitial $interstitial,
        public InterstitialResponse $response,
        public ?Authenticatable $user,
        public array $data
    ) {}
}
