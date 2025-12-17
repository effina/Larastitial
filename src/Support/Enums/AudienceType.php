<?php

declare(strict_types=1);

namespace effina\Larastitial\Support\Enums;

enum AudienceType: string
{
    case All = 'all';
    case Authenticated = 'authenticated';
    case Guest = 'guest';
    case Roles = 'roles';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All Users',
            self::Authenticated => 'Authenticated Only',
            self::Guest => 'Guests Only',
            self::Roles => 'Specific Roles',
            self::Custom => 'Custom Condition',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::All => 'Show to all visitors regardless of authentication status',
            self::Authenticated => 'Show only to logged-in users',
            self::Guest => 'Show only to visitors who are not logged in',
            self::Roles => 'Show only to users with specific roles',
            self::Custom => 'Use a custom condition class to determine eligibility',
        };
    }
}
