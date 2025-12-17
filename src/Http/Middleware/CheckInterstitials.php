<?php

declare(strict_types=1);

namespace effina\Larastitial\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Services\InterstitialManager;
use effina\Larastitial\Support\Enums\InterstitialType;
use Symfony\Component\HttpFoundation\Response;

class CheckInterstitials
{
    public function __construct(
        protected InterstitialManager $manager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Skip for AJAX requests unless explicitly enabled
        if ($request->ajax() && !$request->headers->has('X-Larastitial-Check')) {
            return $next($request);
        }

        // Skip for specific routes
        if ($this->shouldSkipRoute($request)) {
            return $next($request);
        }

        // Get interstitials for the current route
        $routeName = $request->route()?->getName() ?? $request->path();
        $interstitials = $this->manager->getForRoute($routeName);

        if ($interstitials->isEmpty()) {
            return $next($request);
        }

        // Check for full-page interstitials that need redirect
        $fullPage = $interstitials->first(fn (Interstitial $i) => $i->type === InterstitialType::FullPage);

        if ($fullPage) {
            return $this->handleFullPageInterstitial($request, $fullPage);
        }

        // Queue modal/inline interstitials for display
        foreach ($interstitials as $interstitial) {
            $this->manager->queue($interstitial, 'route');
        }

        // Share queued interstitials with views
        View::share('larastitialQueued', $this->manager->getQueued());

        return $next($request);
    }

    /**
     * Handle a full-page interstitial redirect.
     */
    protected function handleFullPageInterstitial(Request $request, Interstitial $interstitial): Response
    {
        // Store the intended URL
        $sessionKey = config('larastitial.full_page.session_key', 'larastitial_intended_url');
        session()->put($sessionKey, $request->fullUrl());

        // Redirect to the interstitial page
        $routePrefix = config('larastitial.full_page.route_prefix', 'interstitial');

        return redirect()->to("/{$routePrefix}/{$interstitial->uuid}");
    }

    /**
     * Determine if the current route should be skipped.
     */
    protected function shouldSkipRoute(Request $request): bool
    {
        $routePrefix = config('larastitial.full_page.route_prefix', 'interstitial');
        $adminPrefix = config('larastitial.admin.prefix', 'admin/interstitials');

        // Skip interstitial routes
        if (str_starts_with($request->path(), $routePrefix)) {
            return true;
        }

        // Skip admin routes
        if (str_starts_with($request->path(), $adminPrefix)) {
            return true;
        }

        // Skip API routes
        if (str_starts_with($request->path(), 'api/')) {
            return true;
        }

        return false;
    }
}
