@extends('larastitial::layouts.admin')

@section('title', 'Statistics - ' . $interstitial->name)

@section('content')
    <div class="admin-header">
        <div>
            <h1 class="admin-title">Statistics</h1>
            <p class="admin-breadcrumb">
                <a href="{{ route('larastitial.admin.index') }}">Interstitials</a> /
                <a href="{{ route('larastitial.admin.show', $interstitial) }}">{{ $interstitial->name }}</a> /
                Statistics
            </p>
        </div>
        <a href="{{ route('larastitial.admin.show', $interstitial) }}" class="btn btn-secondary">Back to Details</a>
    </div>

    <div class="stats-grid">
        @foreach($actionBreakdown as $action => $count)
            <div class="stat-card">
                <div class="stat-value">{{ number_format($count) }}</div>
                <div class="stat-label">{{ ucfirst(str_replace('_', ' ', $action)) }}</div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <h3 style="font-size: 1rem; font-weight: 600;">Views Over Time (Last 30 Days)</h3>
        </div>
        <div class="card-body">
            @if($dailyViews->isEmpty())
                <p style="text-align: center; color: var(--gray-500);">No data available</p>
            @else
                <div style="display: flex; align-items: flex-end; height: 200px; gap: 2px;">
                    @php
                        $maxCount = $dailyViews->max('count') ?: 1;
                    @endphp
                    @foreach($dailyViews as $day)
                        <div
                            style="flex: 1; background: var(--primary); border-radius: 2px 2px 0 0; min-width: 8px; height: {{ ($day->count / $maxCount) * 100 }}%;"
                            title="{{ $day->date }}: {{ $day->count }} views"
                        ></div>
                    @endforeach
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem; font-size: 0.75rem; color: var(--gray-500);">
                    <span>{{ $dailyViews->first()->date ?? '' }}</span>
                    <span>{{ $dailyViews->last()->date ?? '' }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
