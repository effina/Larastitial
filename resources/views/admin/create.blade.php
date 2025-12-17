@extends('larastitial::layouts.admin')

@section('title', 'Create Interstitial')

@section('content')
    <div class="admin-header">
        <div>
            <h1 class="admin-title">Create Interstitial</h1>
            <p class="admin-breadcrumb">
                <a href="{{ route('larastitial.admin.index') }}">Interstitials</a> / Create
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('larastitial.admin.store') }}">
                @csrf
                @include('larastitial::admin.partials.form')

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Create Interstitial</button>
                    <a href="{{ route('larastitial.admin.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
