<?php

declare(strict_types=1);

namespace effina\Larastitial\Support\Enums;

enum ContentType: string
{
    case BladeView = 'blade_view';
    case Database = 'database';
    case Form = 'form';

    public function label(): string
    {
        return match ($this) {
            self::BladeView => 'Blade View',
            self::Database => 'Database Content',
            self::Form => 'Form',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BladeView => 'References a Blade view file for rendering',
            self::Database => 'HTML content stored directly in the database',
            self::Form => 'A form that collects user input',
        };
    }
}
