<?php

declare(strict_types=1);

namespace effina\Larastitial\Contracts;

interface TenantResolver
{
    /**
     * Resolve the current tenant ID.
     *
     * @return int|string|null
     */
    public function resolve(): int|string|null;
}
