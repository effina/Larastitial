<?php

declare(strict_types=1);

namespace effina\Larastitial\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use effina\Larastitial\Models\Interstitial;

class Inline extends Component
{
    public function __construct(
        public Interstitial $interstitial,
        public ?string $class = null
    ) {}

    public function render(): View
    {
        return view('larastitial::components.inline');
    }
}
