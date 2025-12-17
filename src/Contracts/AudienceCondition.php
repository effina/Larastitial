<?php

declare(strict_types=1);

namespace effina\Larastitial\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use effina\Larastitial\Models\Interstitial;

interface AudienceCondition
{
    /**
     * Determine if the user passes the custom audience condition.
     */
    public function passes(?Authenticatable $user, Interstitial $interstitial): bool;
}
