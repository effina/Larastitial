<?php

declare(strict_types=1);

namespace effina\Larastitial\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use effina\Larastitial\Contracts\AudienceCondition;
use effina\Larastitial\Contracts\TenantResolver;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Support\Enums\AudienceType;

class AudienceResolver
{
    public function __construct(
        protected ?TenantResolver $tenantResolver = null
    ) {}

    /**
     * Determine if the user matches the interstitial's audience criteria.
     */
    public function matches(Interstitial $interstitial, ?Authenticatable $user): bool
    {
        // Check tenant scope first
        if (!$this->matchesTenant($interstitial)) {
            return false;
        }

        return match ($interstitial->audience_type) {
            AudienceType::All => true,
            AudienceType::Authenticated => $user !== null,
            AudienceType::Guest => $user === null,
            AudienceType::Roles => $this->matchesRoles($interstitial, $user),
            AudienceType::Custom => $this->matchesCustomCondition($interstitial, $user),
        };
    }

    /**
     * Check if the current tenant matches the interstitial's tenant.
     */
    protected function matchesTenant(Interstitial $interstitial): bool
    {
        if (!config('larastitial.multi_tenant.enabled')) {
            return true;
        }

        $column = config('larastitial.multi_tenant.column', 'tenant_id');
        $interstitialTenant = $interstitial->{$column};

        // Global interstitials (null tenant) are shown to all tenants
        if ($interstitialTenant === null) {
            return true;
        }

        // Resolve current tenant
        $currentTenant = $this->tenantResolver?->resolve();

        return $currentTenant !== null && $interstitialTenant == $currentTenant;
    }

    /**
     * Check if the user has any of the required roles.
     */
    protected function matchesRoles(Interstitial $interstitial, ?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        $requiredRoles = $interstitial->audience_roles ?? [];

        if (empty($requiredRoles)) {
            return true;
        }

        // Check if the user model has a hasRole method (Spatie, Bouncer, etc.)
        if (method_exists($user, 'hasRole')) {
            foreach ($requiredRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }

        // Check if the user model has a hasAnyRole method
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($requiredRoles);
        }

        // Fallback: check for a 'role' or 'roles' attribute
        if (isset($user->role)) {
            return in_array($user->role, $requiredRoles);
        }

        if (isset($user->roles) && is_iterable($user->roles)) {
            foreach ($user->roles as $role) {
                $roleName = is_object($role) ? ($role->name ?? $role->slug ?? (string) $role) : $role;
                if (in_array($roleName, $requiredRoles)) {
                    return true;
                }
            }
            return false;
        }

        // If we can't determine roles, default to not showing
        return false;
    }

    /**
     * Check if the user passes the custom audience condition.
     */
    protected function matchesCustomCondition(Interstitial $interstitial, ?Authenticatable $user): bool
    {
        $conditionClass = $interstitial->audience_condition;

        if (empty($conditionClass)) {
            return true;
        }

        if (!class_exists($conditionClass)) {
            return false;
        }

        $condition = app($conditionClass);

        if (!$condition instanceof AudienceCondition) {
            return false;
        }

        return $condition->passes($user, $interstitial);
    }
}
