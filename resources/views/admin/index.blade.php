@extends('larastitial::layouts.admin')

@section('title', 'Interstitials')

@section('content')
    <div class="admin-header">
        <div>
            <h1 class="admin-title">Interstitials</h1>
            <p class="admin-breadcrumb">Manage your interstitials, modals, and announcements</p>
        </div>
        <a href="{{ route('larastitial.admin.create') }}" class="btn btn-primary">
            + New Interstitial
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('larastitial.admin.index') }}" style="display: flex; gap: 1rem; flex: 1;">
                <input
                    type="text"
                    name="search"
                    placeholder="Search..."
                    value="{{ request('search') }}"
                    class="form-input"
                    style="max-width: 300px;"
                >
                <select name="type" class="form-select" style="max-width: 150px;">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" {{ request('type') === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="form-select" style="max-width: 150px;">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Audience</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Priority</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($interstitials as $interstitial)
                    <tr>
                        <td>
                            <a href="{{ route('larastitial.admin.show', $interstitial) }}" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                {{ $interstitial->name }}
                            </a>
                            <div style="font-size: 0.75rem; color: var(--gray-500);">
                                {{ $interstitial->title }}
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-secondary">{{ $interstitial->type->label() }}</span>
                        </td>
                        <td>
                            <span class="badge badge-secondary">{{ $interstitial->audience_type->label() }}</span>
                        </td>
                        <td>
                            @if($interstitial->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-warning">Inactive</span>
                            @endif
                        </td>
                        <td>{{ number_format($interstitial->views_count) }}</td>
                        <td>{{ $interstitial->priority }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('larastitial.admin.edit', $interstitial) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('larastitial.admin.toggle', $interstitial) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        {{ $interstitial->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                            No interstitials found. <a href="{{ route('larastitial.admin.create') }}">Create your first one</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($interstitials->hasPages())
            <div class="pagination">
                {{ $interstitials->links() }}
            </div>
        @endif
    </div>
@endsection
