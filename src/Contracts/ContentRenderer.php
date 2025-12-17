<?php

declare(strict_types=1);

namespace effina\Larastitial\Contracts;

use effina\Larastitial\Models\Interstitial;

interface ContentRenderer
{
    /**
     * Render the interstitial content.
     */
    public function render(Interstitial $interstitial, array $data = []): string;
}
