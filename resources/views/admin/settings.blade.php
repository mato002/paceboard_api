@extends('admin.layout')

@section('title', 'Settings — PaceBoard Admin')
@section('page_title', 'Settings')
@section('page_subtitle', 'App configuration, broadcasts, and leaderboards')

@section('content')
<div class="grid-2">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-sliders"></i> App settings</div>
        </div>
        <div style="padding:1.25rem;">
            <form method="POST" action="/admin/settings">
                @csrf @method('PUT')
                <div class="form-group">
                    <label><i class="fa-solid fa-gauge-high" style="color:var(--muted)"></i> Speed limit (km/h)</label>
                    <input type="number" name="speed_limit_kmh" value="{{ $settings['speed_limit_kmh'] }}" min="30" max="200">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-ranking-star" style="color:var(--muted)"></i> Ranking minimum score</label>
                    <input type="number" name="ranking_min_score" value="{{ $settings['ranking_min_score'] }}" min="0" max="100">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-gas-pump" style="color:var(--muted)"></i> Fuel consumption (L/100km)</label>
                    <input type="number" step="0.1" name="fuel_consumption_per_100km" value="{{ $settings['fuel_consumption_per_100km'] }}">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-map" style="color:var(--muted)"></i> Map provider</label>
                    <input type="text" value="OpenStreetMap (Leaflet)" readonly style="background:var(--surface-alt);cursor:not-allowed">
                    <input type="hidden" name="map_provider" value="openstreetmap">
                    <p style="font-size:.8rem;color:var(--muted);margin:.35rem 0 0">Free OpenStreetMap tiles — no paid API key required.</p>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="maintenance_mode" value="1" {{ $settings['maintenance_mode'] ? 'checked' : '' }}> <i class="fa-solid fa-wrench"></i> Maintenance mode</label>
                </div>
                <div class="form-group">
                    <label>Maintenance message</label>
                    <textarea name="maintenance_message" rows="2">{{ $settings['maintenance_message'] }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save settings</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-bullhorn"></i> Broadcast notification</div>
        </div>
        <div style="padding:1.25rem;">
            <form method="POST" action="/admin/broadcast"
                  data-confirm="A push notification will be sent to all users with registered devices."
                  data-confirm-title="Send broadcast?"
                  data-confirm-icon="question"
                  data-confirm-button="Send now">
                @csrf
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required maxlength="255" placeholder="Announcement title">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="body" rows="3" required maxlength="1000" placeholder="Message to all users…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send broadcast</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-trophy"></i> Leaderboards</div>
        </div>
        <div style="padding:1.25rem;">
            <p style="font-size:.875rem;color:var(--muted);margin:0 0 1rem">Reset leaderboard rankings. This action cannot be undone.</p>
            <form method="POST" action="/admin/leaderboards/reset"
                  data-confirm="All leaderboard rankings will be permanently cleared. This cannot be undone."
                  data-confirm-title="Reset leaderboards?"
                  data-confirm-icon="error"
                  data-confirm-button="Yes, reset all"
                  data-confirm-danger="true">
                @csrf
                <div class="form-group">
                    <label>Reset scope</label>
                    <select name="scope">
                        <option value="global">Global only</option>
                        <option value="routes">Route leaderboards only</option>
                        <option value="all">All leaderboards</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-rotate-left"></i> Reset leaderboards</button>
            </form>
        </div>
    </div>
</div>
@endsection
