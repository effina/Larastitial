<?php

declare(strict_types=1);

namespace effina\Larastitial\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use effina\Larastitial\Models\Interstitial;

class Modal extends Component
{
    public function __construct(
        public Interstitial $interstitial,
        public ?string $id = null,
        public ?string $class = null
    ) {
        $this->id = $id ?? 'larastitial-modal-' . $interstitial->uuid;
    }

    public function render(): View
    {
        return view('larastitial::components.modal');
    }
}
