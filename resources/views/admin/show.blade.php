@extends('larastitial::layouts.admin')

@section('title', $interstitial->name)

@section('content')
    <div class="admin-header">
        <div>
            <h1 class="admin-title">{{ $interstitial->name }}</h1>
            <p class="admin-breadcrumb">
                <a href="{{ route('larastitial.admin.index') }}">Interstitials</a> / {{ $interstitial->name }}
            </p>
        </div>
        <div class="actions">
            <a href="{{ route('larastitial.admin.edit', $interstitial) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('larastitial.admin.stats', $interstitial) }}" class="btn btn-secondary">View Stats</a>
            <form method="POST" action="{{ route('larastitial.admin.toggle', $interstitial) }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    {{ $interstitial->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['total_views']) }}</div>
            <div class="stat-label">Total Views</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['completed']) }}</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['dismissed']) }}</div>
            <div class="stat-label">Dismissed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['total_responses']) }}</div>
            <div class="stat-label">Form Responses</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <div class="card">
            <div class="card-header">
                <h3 style="font-size: 1rem; font-weight: 600;">Details</h3>
            </div>
            <div class="card-body">
                <dl style="display: grid; grid-template-columns: 150px 1fr; gap: 0.75rem;">
                    <dt style="color: var(--gray-500); font-size: 0.875rem;">Title</dt>
                    <dd>{{ $interstitial->title }}</dd>

                    <dt style="color: var(--gray-500); font-size: 0.875rem;">Type</dt>
                    <dd><span class="badge badge-secondary">{{ $interstitial->type->label() }}</span></dd>

                    <dt style="color: var(--gray-500); font-size: 0.875rem;">Content Type</dt>
                    <dd><span class="badge badge-secondary">{{ $interstitial->content_type->label() }}</span></dd>

                    <dt style="color: var(--gray-500); font-size: 0.875rem;">Status</dt>
                    <dd>
                        @if($interstitial->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-warning">Inactive</span>
                        @endif
                    </dd>

                    <dt style="color: var(--gray-500); font-size: 0.875rem;">Audience</dt>
                    <dd>{{ $interstitial->audience_type->label() }}</dd>

                    <dt style="color: var(--gray-500); font-size: 0.875rem;">Frequency</dt>
                    <dd>{{ $interstitial->frequency->label() }}</dd>

                    <dt style="color: var(--gray-500); font-size: 0.875rem;">Priority</dt>
                    <dd>{{ $interstitial->priority }}</dd>

                    @if($interstitial->trigger_event)
                        <dt style="color: var(--gray-500); font-size: 0.875rem;">Trigger Event</dt>
                        <dd><code style="font-size: 0.75rem;">{{ $interstitial->trigger_event }}</code></dd>
                    @endif

                    @if($interstitial->trigger_routes)
                        <dt style="color: var(--gray-500); font-size: 0.875rem;">Trigger Routes</dt>
                        <dd>{{ implode(', ', $interstitial->trigger_routes) }}</dd>
                    @endif

                    @if($interstitial->isScheduled())
                        <dt style="color: var(--gray-500); font-size: 0.875rem;">Schedule</dt>
                        <dd>
                            {{ $interstitial->trigger_schedule_start?->format('M j, Y g:i A') ?? 'No start' }}
                            -
                            {{ $interstitial->trigger_schedule_end?->format('M j, Y g:i A') ?? 'No end' }}
                        </dd>
                    @endif

                    <dt style="color: var(--gray-500); font-size: 0.875rem;">UUID</dt>
                    <dd><code style="font-size: 0.75rem;">{{ $interstitial->uuid }}</code></dd>

                    <dt style="color: var(--gray-500); font-size: 0.875rem;">Created</dt>
                    <dd>{{ $interstitial->created_at->format('M j, Y g:i A') }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 style="font-size: 1rem; font-weight: 600;">Recent Activity</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                @if($recentViews->isEmpty())
                    <p style="padding: 1.5rem; text-align: center; color: var(--gray-500);">No views yet</p>
                @else
                    <ul style="list-style: none;">
                        @foreach($recentViews as $view)
                            <li style="padding: 0.75rem 1.5rem; border-bottom: 1px solid var(--gray-200); font-size: 0.875rem;">
                                <span class="badge badge-secondary">{{ $view->action->label() }}</span>
                                <span style="color: var(--gray-500); margin-left: 0.5rem;">
                                    {{ $view->viewed_at->diffForHumans() }}
                                </span>
                                @if($view->user)
                                    <span style="color: var(--gray-600);">by {{ $view->user->name ?? $view->user->email ?? 'User #' . $view->user_id }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    @if($interstitial->content)
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3 style="font-size: 1rem; font-weight: 600;">Content Preview</h3>
            </div>
            <div class="card-body">
                <div style="background: var(--gray-50); padding: 1.5rem; border-radius: 6px; border: 1px solid var(--gray-200);">
                    {!! $interstitial->content !!}
                </div>
            </div>
        </div>
    @endif
@endsection
