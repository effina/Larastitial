<?php

declare(strict_types=1);

namespace effina\Larastitial\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Services\InterstitialManager;
use effina\Larastitial\Support\Enums\ViewAction;

class InterstitialController extends Controller
{
    public function __construct(
        protected InterstitialManager $manager
    ) {}

    /**
     * Display a full-page interstitial.
     */
    public function show(string $uuid): View|RedirectResponse
    {
        $interstitial = Interstitial::where('uuid', $uuid)->firstOrFail();

        // Check if user should see this interstitial
        if (!$this->manager->shouldShow($interstitial)) {
            return $this->redirectAfterInterstitial($interstitial);
        }

        // Mark as viewed
        $this->manager->markViewed($interstitial, null, 'viewed');

        return view('larastitial::full-page', [
            'interstitial' => $interstitial,
            'content' => $this->manager->render($interstitial),
        ]);
    }

    /**
     * Handle an interstitial action (dismiss, complete, don't show again).
     */
    public function action(Request $request, string $uuid): RedirectResponse
    {
        $interstitial = Interstitial::where('uuid', $uuid)->firstOrFail();

        $action = $request->input('action', 'dismissed');
        $ctaClicked = $request->input('cta');

        // Record the action
        $viewAction = match ($action) {
            'dismiss', 'dismissed' => ViewAction::Dismissed,
            'complete', 'completed' => ViewAction::Completed,
            'dont_show_again' => ViewAction::DontShowAgain,
            default => ViewAction::Dismissed,
        };

        $this->manager->markViewed($interstitial, null, $viewAction->value);

        return $this->redirectAfterInterstitial($interstitial, $ctaClicked);
    }

    /**
     * Handle a form submission from an interstitial.
     */
    public function respond(Request $request, string $uuid): RedirectResponse
    {
        $interstitial = Interstitial::where('uuid', $uuid)->firstOrFail();

        // Get all form data except internal fields
        $data = $request->except(['_token', '_method', 'action', 'cta']);

        // Record the response
        $this->manager->recordResponse($interstitial, null, $data);

        return $this->redirectAfterInterstitial($interstitial);
    }

    /**
     * Redirect the user after completing an interstitial.
     */
    protected function redirectAfterInterstitial(Interstitial $interstitial, ?string $ctaClicked = null): RedirectResponse
    {
        // Check for CTA-specific redirect
        if ($ctaClicked && $interstitial->cta_buttons) {
            foreach ($interstitial->cta_buttons as $button) {
                if (($button['id'] ?? $button['label'] ?? null) === $ctaClicked && !empty($button['url'])) {
                    return redirect()->to($button['url']);
                }
            }
        }

        // Check for interstitial-specific redirect
        if ($interstitial->redirect_after) {
            return redirect()->to($interstitial->redirect_after);
        }

        // Redirect to original intended URL if configured
        if (config('larastitial.full_page.redirect_to_original', true)) {
            $sessionKey = config('larastitial.full_page.session_key', 'larastitial_intended_url');
            $intendedUrl = session()->pull($sessionKey);

            if ($intendedUrl) {
                return redirect()->to($intendedUrl);
            }
        }

        // Fallback to home
        return redirect()->to('/');
    }
}
