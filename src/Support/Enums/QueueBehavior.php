<?php

declare(strict_types=1);

namespace effina\Larastitial\Support\Enums;

enum QueueBehavior: string
{
    case Inherit = 'inherit';
    case ShowWithOthers = 'show_with_others';
    case Exclusive = 'exclusive';

    public function label(): string
    {
        return match ($this) {
            self::Inherit => 'Use Global Setting',
            self::ShowWithOthers => 'Show With Others',
            self::Exclusive => 'Exclusive (Show Alone)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Inherit => 'Use the global queue_behavior configuration setting',
            self::ShowWithOthers => 'Can be shown alongside other interstitials in sequence',
            self::Exclusive => 'When shown, no other interstitials should appear',
        };
    }
}
