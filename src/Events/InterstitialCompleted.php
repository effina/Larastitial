<?php

declare(strict_types=1);

namespace effina\Larastitial\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use effina\Larastitial\Models\Interstitial;

class InterstitialCompleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Interstitial $interstitial,
        public ?Authenticatable $user,
        public ?string $sessionId = null,
        public ?string $ctaClicked = null
    ) {}
}
