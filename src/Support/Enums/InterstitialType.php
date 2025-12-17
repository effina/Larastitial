<?php

declare(strict_types=1);

namespace effina\Larastitial\Support\Enums;

enum InterstitialType: string
{
    case Modal = 'modal';
    case FullPage = 'full_page';
    case Inline = 'inline';

    public function label(): string
    {
        return match ($this) {
            self::Modal => 'Modal Overlay',
            self::FullPage => 'Full Page',
            self::Inline => 'Inline Content',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Modal => 'A modal dialog that overlays the current page',
            self::FullPage => 'Redirects user to a dedicated interstitial page',
            self::Inline => 'Renders content inline within the page at specified slots',
        };
    }
}
