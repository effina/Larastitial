<?php

declare(strict_types=1);

namespace effina\Larastitial\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use effina\Larastitial\Http\Requests\StoreInterstitialRequest;
use effina\Larastitial\Http\Requests\UpdateInterstitialRequest;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Support\Enums\AudienceType;
use effina\Larastitial\Support\Enums\ContentType;
use effina\Larastitial\Support\Enums\Frequency;
use effina\Larastitial\Support\Enums\InterstitialType;
use effina\Larastitial\Support\Enums\QueueBehavior;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $gate = config('larastitial.admin.gate', 'manage-interstitials');
            if (!Gate::allows($gate)) {
                abort(403);
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of interstitials.
     */
    public function index(Request $request): View
    {
        $query = Interstitial::query()->withCount(['views', 'responses']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by status
        if ($request->input('status') === 'active') {
            $query->active();
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        // Include trashed if requested
        if ($request->input('trashed')) {
            $query->withTrashed();
        }

        $interstitials = $query->orderByDesc('updated_at')->paginate(20);

        return view('larastitial::admin.index', [
            'interstitials' => $interstitials,
            'types' => InterstitialType::cases(),
        ]);
    }

    /**
     * Show the form for creating a new interstitial.
     */
    public function create(): View
    {
        return view('larastitial::admin.create', [
            'interstitialTypes' => InterstitialType::cases(),
            'contentTypes' => ContentType::cases(),
            'audienceTypes' => AudienceType::cases(),
            'frequencies' => Frequency::cases(),
            'queueBehaviors' => QueueBehavior::cases(),
        ]);
    }

    /**
     * Store a newly created interstitial.
     */
    public function store(StoreInterstitialRequest $request): RedirectResponse
    {
        $interstitial = Interstitial::create($request->validated());

        return redirect()
            ->route('larastitial.admin.show', $interstitial)
            ->with('success', 'Interstitial created successfully.');
    }

    /**
     * Display the specified interstitial.
     */
    public function show(Interstitial $interstitial): View
    {
        $interstitial->loadCount(['views', 'responses']);

        // Get recent views
        $recentViews = $interstitial->views()
            ->with('user')
            ->orderByDesc('viewed_at')
            ->limit(10)
            ->get();

        // Get view statistics
        $stats = [
            'total_views' => $interstitial->views_count,
            'total_responses' => $interstitial->responses_count,
            'dismissed' => $interstitial->views()->dismissed()->count(),
            'completed' => $interstitial->views()->completed()->count(),
            'dont_show_again' => $interstitial->views()->dontShowAgain()->count(),
        ];

        return view('larastitial::admin.show', [
            'interstitial' => $interstitial,
            'recentViews' => $recentViews,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified interstitial.
     */
    public function edit(Interstitial $interstitial): View
    {
        return view('larastitial::admin.edit', [
            'interstitial' => $interstitial,
            'interstitialTypes' => InterstitialType::cases(),
            'contentTypes' => ContentType::cases(),
            'audienceTypes' => AudienceType::cases(),
            'frequencies' => Frequency::cases(),
            'queueBehaviors' => QueueBehavior::cases(),
        ]);
    }

    /**
     * Update the specified interstitial.
     */
    public function update(UpdateInterstitialRequest $request, Interstitial $interstitial): RedirectResponse
    {
        $interstitial->update($request->validated());

        return redirect()
            ->route('larastitial.admin.show', $interstitial)
            ->with('success', 'Interstitial updated successfully.');
    }

    /**
     * Remove the specified interstitial (soft delete).
     */
    public function destroy(Interstitial $interstitial): RedirectResponse
    {
        $interstitial->delete();

        return redirect()
            ->route('larastitial.admin.index')
            ->with('success', 'Interstitial deleted successfully.');
    }

    /**
     * Restore a soft-deleted interstitial.
     */
    public function restore(int $id): RedirectResponse
    {
        $interstitial = Interstitial::withTrashed()->findOrFail($id);
        $interstitial->restore();

        return redirect()
            ->route('larastitial.admin.show', $interstitial)
            ->with('success', 'Interstitial restored successfully.');
    }

    /**
     * Display statistics for an interstitial.
     */
    public function stats(Interstitial $interstitial): View
    {
        // Daily views for the last 30 days
        $dailyViews = $interstitial->views()
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->where('viewed_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Action breakdown
        $actionBreakdown = $interstitial->views()
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->get()
            ->pluck('count', 'action');

        return view('larastitial::admin.stats', [
            'interstitial' => $interstitial,
            'dailyViews' => $dailyViews,
            'actionBreakdown' => $actionBreakdown,
        ]);
    }

    /**
     * Toggle the active status of an interstitial.
     */
    public function toggle(Interstitial $interstitial): RedirectResponse
    {
        $interstitial->update(['is_active' => !$interstitial->is_active]);

        $status = $interstitial->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Interstitial {$status} successfully.");
    }

    /**
     * Duplicate an interstitial.
     */
    public function duplicate(Interstitial $interstitial): RedirectResponse
    {
        $newInterstitial = $interstitial->replicate();
        $newInterstitial->name = $interstitial->name . '-copy-' . time();
        $newInterstitial->uuid = null; // Will be regenerated
        $newInterstitial->is_active = false;
        $newInterstitial->save();

        return redirect()
            ->route('larastitial.admin.edit', $newInterstitial)
            ->with('success', 'Interstitial duplicated successfully.');
    }
}
