<?php

declare(strict_types=1);

namespace effina\Larastitial\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use effina\Larastitial\Models\Interstitial;

class InterstitialPolicy
{
    /**
     * Determine whether the user can view any interstitials.
     */
    public function viewAny(Authenticatable $user): bool
    {
        return $this->checkGate($user);
    }

    /**
     * Determine whether the user can view the interstitial.
     */
    public function view(Authenticatable $user, Interstitial $interstitial): bool
    {
        return $this->checkGate($user);
    }

    /**
     * Determine whether the user can create interstitials.
     */
    public function create(Authenticatable $user): bool
    {
        return $this->checkGate($user);
    }

    /**
     * Determine whether the user can update the interstitial.
     */
    public function update(Authenticatable $user, Interstitial $interstitial): bool
    {
        return $this->checkGate($user);
    }

    /**
     * Determine whether the user can delete the interstitial.
     */
    public function delete(Authenticatable $user, Interstitial $interstitial): bool
    {
        return $this->checkGate($user);
    }

    /**
     * Determine whether the user can restore the interstitial.
     */
    public function restore(Authenticatable $user, Interstitial $interstitial): bool
    {
        return $this->checkGate($user);
    }

    /**
     * Determine whether the user can permanently delete the interstitial.
     */
    public function forceDelete(Authenticatable $user, Interstitial $interstitial): bool
    {
        return $this->checkGate($user);
    }

    /**
     * Check the configured gate for interstitial management.
     */
    protected function checkGate(Authenticatable $user): bool
    {
        $gateName = config('larastitial.admin.gate', 'manage-interstitials');

        return Gate::forUser($user)->allows($gateName);
    }
}
