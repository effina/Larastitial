<?php

declare(strict_types=1);

namespace effina\Larastitial\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Services\InterstitialManager;
use effina\Larastitial\Support\Enums\ViewAction;

class ApiController extends Controller
{
    public function __construct(
        protected InterstitialManager $manager
    ) {}

    /**
     * Get applicable interstitials for the current context.
     */
    public function applicable(Request $request): JsonResponse
    {
        $context = $request->input('context', 'page_load');
        $route = $request->input('route');
        $slot = $request->input('slot');

        $interstitials = match ($context) {
            'route' => $route ? $this->manager->getForRoute($route) : collect(),
            'slot', 'inline' => $slot ? $this->manager->getForSlot($slot) : collect(),
            default => $this->manager->getApplicable(auth()->user(), $context),
        };

        return response()->json([
            'data' => $interstitials->map(fn (Interstitial $i) => $this->transformInterstitial($i)),
            'meta' => [
                'count' => $interstitials->count(),
                'context' => $context,
            ],
        ]);
    }

    /**
     * Get a specific interstitial by UUID.
     */
    public function show(string $uuid): JsonResponse
    {
        $interstitial = Interstitial::where('uuid', $uuid)->firstOrFail();

        if (!$this->manager->shouldShow($interstitial)) {
            return response()->json([
                'error' => 'Interstitial not available',
                'code' => 'not_available',
            ], 403);
        }

        // Mark as viewed
        $this->manager->markViewed($interstitial, null, 'viewed');

        return response()->json([
            'data' => $this->transformInterstitial($interstitial, true),
        ]);
    }

    /**
     * Handle an interstitial action.
     */
    public function action(Request $request, string $uuid): JsonResponse
    {
        $interstitial = Interstitial::where('uuid', $uuid)->firstOrFail();

        $action = $request->input('action', 'dismissed');

        $viewAction = match ($action) {
            'dismiss', 'dismissed' => ViewAction::Dismissed,
            'complete', 'completed' => ViewAction::Completed,
            'dont_show_again' => ViewAction::DontShowAgain,
            default => ViewAction::Dismissed,
        };

        $this->manager->markViewed($interstitial, null, $viewAction->value);

        return response()->json([
            'success' => true,
            'action' => $viewAction->value,
            'redirect' => $this->getRedirectUrl($interstitial, $request->input('cta')),
        ]);
    }

    /**
     * Handle a form submission.
     */
    public function respond(Request $request, string $uuid): JsonResponse
    {
        $interstitial = Interstitial::where('uuid', $uuid)->firstOrFail();

        $data = $request->except(['_token', '_method', 'action', 'cta']);

        $response = $this->manager->recordResponse($interstitial, null, $data);

        return response()->json([
            'success' => true,
            'response_id' => $response?->id,
            'redirect' => $this->getRedirectUrl($interstitial),
        ]);
    }

    /**
     * Transform an interstitial for API response.
     */
    protected function transformInterstitial(Interstitial $interstitial, bool $includeContent = false): array
    {
        $data = [
            'uuid' => $interstitial->uuid,
            'name' => $interstitial->name,
            'title' => $interstitial->title,
            'type' => $interstitial->type->value,
            'content_type' => $interstitial->content_type->value,
            'priority' => $interstitial->priority,
            'cta_buttons' => $interstitial->cta_buttons,
            'allow_dismiss' => $interstitial->allow_dismiss,
            'allow_dont_show_again' => $interstitial->allow_dont_show_again,
            'metadata' => $interstitial->metadata,
        ];

        if ($includeContent) {
            $data['content'] = $this->manager->render($interstitial);
        }

        return $data;
    }

    /**
     * Get the redirect URL for an interstitial.
     */
    protected function getRedirectUrl(Interstitial $interstitial, ?string $ctaClicked = null): ?string
    {
        // Check for CTA-specific redirect
        if ($ctaClicked && $interstitial->cta_buttons) {
            foreach ($interstitial->cta_buttons as $button) {
                if (($button['id'] ?? $button['label'] ?? null) === $ctaClicked && !empty($button['url'])) {
                    return $button['url'];
                }
            }
        }

        return $interstitial->redirect_after;
    }
}
