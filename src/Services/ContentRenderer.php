<?php

declare(strict_types=1);

namespace effina\Larastitial\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use effina\Larastitial\Contracts\ContentRenderer as ContentRendererContract;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Support\Enums\ContentType;

class ContentRenderer implements ContentRendererContract
{
    /**
     * Render the interstitial content.
     */
    public function render(Interstitial $interstitial, array $data = []): string
    {
        $data = array_merge([
            'interstitial' => $interstitial,
        ], $data);

        return match ($interstitial->content_type) {
            ContentType::BladeView => $this->renderBladeView($interstitial, $data),
            ContentType::Database => $this->renderDatabaseContent($interstitial, $data),
            ContentType::Form => $this->renderForm($interstitial, $data),
        };
    }

    /**
     * Render content from a Blade view file.
     */
    protected function renderBladeView(Interstitial $interstitial, array $data): string
    {
        $viewName = $interstitial->blade_view;

        if (empty($viewName)) {
            return '';
        }

        // Check if the view exists
        if (!View::exists($viewName)) {
            return "<!-- View '{$viewName}' not found -->";
        }

        return View::make($viewName, $data)->render();
    }

    /**
     * Render HTML content stored in the database.
     */
    protected function renderDatabaseContent(Interstitial $interstitial, array $data): string
    {
        $content = $interstitial->content ?? '';

        // Allow basic Blade syntax in database content using Laravel's safe render method
        if (str_contains($content, '{{') || str_contains($content, '{!!')) {
            return Blade::render($content, $data);
        }

        return $content;
    }

    /**
     * Render form content.
     */
    protected function renderForm(Interstitial $interstitial, array $data): string
    {
        // If there's a custom blade view for the form, use it
        if (!empty($interstitial->blade_view) && View::exists($interstitial->blade_view)) {
            return View::make($interstitial->blade_view, $data)->render();
        }

        // Otherwise, render the database content which should contain the form HTML
        return $this->renderDatabaseContent($interstitial, $data);
    }
}
