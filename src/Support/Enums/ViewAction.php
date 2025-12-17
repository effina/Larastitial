<?php

declare(strict_types=1);

namespace effina\Larastitial\Support\Enums;

enum ViewAction: string
{
    case Viewed = 'viewed';
    case Dismissed = 'dismissed';
    case Completed = 'completed';
    case DontShowAgain = 'dont_show_again';

    public function label(): string
    {
        return match ($this) {
            self::Viewed => 'Viewed',
            self::Dismissed => 'Dismissed',
            self::Completed => 'Completed',
            self::DontShowAgain => "Don't Show Again",
        };
    }
}
