<?php

declare(strict_types=1);

namespace effina\Larastitial\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        // Only GET requests can be redirected safely; leave form submissions alone
        // so session-queued interstitials survive until the next page load
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Skip for specific routes
        if ($this->shouldSkipRoute($request)) {
            return $next($request);
        }

        // Load any interstitials queued from previous request (e.g., from events)
        $this->manager->loadFromSession();

        // Full-page interstitials the user just came back from (prevents redirect loops)
        $passedKey = config('larastitial.session.passed_key', 'larastitial_full_page_passed');
        $passed = (array) session()->get($passedKey, []);

        $isRedirectable = fn (Interstitial $i) => $i->type === InterstitialType::FullPage
            && !in_array($i->id, $passed, true);

        // Check for full-page interstitials from session (event-triggered)
        $sessionFullPage = $this->manager->getQueued()->first($isRedirectable);

        if ($sessionFullPage) {
            return $this->handleFullPageInterstitial($request, $sessionFullPage);
        }

        // Get interstitials for the current route
        $routeName = $request->route()?->getName() ?? $request->path();
        $routeInterstitials = $this->manager->getForRoute($routeName);

        // Check for full-page interstitials from route
        $routeFullPage = $routeInterstitials->first($isRedirectable);

        if ($routeFullPage) {
            return $this->handleFullPageInterstitial($request, $routeFullPage);
        }

        // Queue route-based modal/inline interstitials for display
        foreach ($routeInterstitials as $interstitial) {
            if ($interstitial->type === InterstitialType::FullPage) {
                continue;
            }

            $this->manager->queue($interstitial, 'route');
        }

        // Get all queued interstitials (from session + route)
        $queued = $this->manager->getQueued();

        // Share queued interstitials with views
        View::share('larastitialQueued', $queued);

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
        $prefixes = [
            config('larastitial.full_page.route_prefix', 'interstitial'),
            config('larastitial.admin.prefix', 'admin/interstitials'),
            'api',
        ];

        foreach ($prefixes as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($request->is($prefix, $prefix . '/*')) {
                return true;
            }
        }

        return false;
    }
}
