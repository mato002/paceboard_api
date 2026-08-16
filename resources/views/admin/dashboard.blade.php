@extends('admin.layout')

@section('title', 'Dashboard — PaceBoard Admin')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Real-time overview of drivers, trips, and platform health')

@section('content')
<div class="page-hero">
    <img src="https://images.pexels.com/photos/3802508/pexels-photo-3802508.jpeg?auto=compress&cs=tinysrgb&w=1600"
         alt="Highway at night">
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <h2>Welcome back, {{ auth()->user()->name }}</h2>
        <p>Monitor fleet activity, safety reports, and emergency alerts across the PaceBoard network.</p>
        <div class="page-hero-actions">
            <a href="/admin/users" class="btn btn-primary btn-sm" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-users"></i> View users</a>
            <a href="/admin/sos" class="btn btn-ghost btn-sm" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-triangle-exclamation"></i> SOS center</a>
        </div>
    </div>
</div>

<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
        <div class="stat-body">
            <h3>Total users</h3>
            <p class="value">{{ number_format($stats['users']) }}</p>
            <p class="meta"><i class="fa-solid fa-user-check"></i> Registered drivers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-route"></i></div>
        <div class="stat-body">
            <h3>Total trips</h3>
            <p class="value">{{ number_format($stats['trips']) }}</p>
            <p class="meta"><i class="fa-solid fa-calendar-day"></i> {{ $stats['trips_today'] }} today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-flag"></i></div>
        <div class="stat-body">
            <h3>Active reports</h3>
            <p class="value">{{ number_format($stats['reports']) }}</p>
            <p class="meta"><i class="fa-solid fa-road"></i> Road hazards & cameras</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon slate"><i class="fa-solid fa-road"></i></div>
        <div class="stat-body">
            <h3>Total distance</h3>
            <p class="value">{{ number_format($stats['total_distance']) }}<span style="font-size:.9rem;font-weight:600;color:var(--muted)"> km</span></p>
            <p class="meta"><i class="fa-solid fa-chart-line"></i> Platform-wide</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon {{ $stats['active_sos'] > 0 ? 'red' : 'green' }}">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-body">
            <h3>Active SOS</h3>
            <p class="value" style="{{ $stats['active_sos'] > 0 ? 'color:var(--danger)' : '' }}">{{ $stats['active_sos'] }}</p>
            <p class="meta">
                @if($stats['active_sos'] > 0)
                    <i class="fa-solid fa-circle" style="color:var(--danger);font-size:.5rem"></i> Requires attention
                @else
                    <i class="fa-solid fa-shield-halved"></i> All clear
                @endif
            </p>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    @include('admin.panels.recent-users')

    @include('admin.panels.recent-reports')
</div>

<div style="margin-top:1.5rem">
    @include('admin.panels.recent-trips')
</div>
@endsection

@push('styles')
<style>
@media (max-width: 900px) {
    .content turbo-frame + turbo-frame,
    .content > div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>
@endpush
