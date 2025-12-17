<?php

declare(strict_types=1);

namespace effina\Larastitial\Support\Enums;

enum Frequency: string
{
    case Always = 'always';
    case Once = 'once';
    case OncePerSession = 'once_per_session';
    case EveryXDays = 'every_x_days';

    public function label(): string
    {
        return match ($this) {
            self::Always => 'Every Time',
            self::Once => 'Once Ever',
            self::OncePerSession => 'Once Per Session',
            self::EveryXDays => 'Every X Days',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Always => 'Show every time conditions are met',
            self::Once => 'Show once and never again for this user',
            self::OncePerSession => 'Show once per browser session',
            self::EveryXDays => 'Show again after specified number of days',
        };
    }
}
