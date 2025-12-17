@extends('larastitial::layouts.admin')

@section('title', 'Edit Interstitial')

@section('content')
    <div class="admin-header">
        <div>
            <h1 class="admin-title">Edit Interstitial</h1>
            <p class="admin-breadcrumb">
                <a href="{{ route('larastitial.admin.index') }}">Interstitials</a> /
                <a href="{{ route('larastitial.admin.show', $interstitial) }}">{{ $interstitial->name }}</a> /
                Edit
            </p>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('larastitial.admin.duplicate', $interstitial) }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary">Duplicate</button>
            </form>
            <form method="POST" action="{{ route('larastitial.admin.destroy', $interstitial) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this interstitial?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('larastitial.admin.update', $interstitial) }}">
                @csrf
                @method('PUT')
                @include('larastitial::admin.partials.form')

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Update Interstitial</button>
                    <a href="{{ route('larastitial.admin.show', $interstitial) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
