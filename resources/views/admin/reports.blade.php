@extends('admin.layout')

@section('title', 'Road Alerts — PaceBoard Admin')
@section('page_title', 'Road Alerts')
@section('page_subtitle', 'Community hazard reports saved from the driver app')

@section('content')
<div class="alerts-layout" style="display:grid;grid-template-columns:320px 1fr;gap:1.5rem;align-items:start;">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-plus-circle"></i> Post an alert</div>
        </div>
        <div style="padding:1.25rem;">
            <form method="POST" action="/admin/reports"
                  data-confirm="This alert will appear to drivers in the app."
                  data-confirm-title="Post road alert?"
                  data-confirm-icon="question"
                  data-confirm-button="Post alert">
                @csrf
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        @foreach(['accident','pothole','speed_camera','traffic','police','hazard','road_closure','construction','flooding','debris','school_zone','breakdown'] as $option)
                            <option value="{{ $option }}">{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>Latitude</label><input type="text" name="latitude" required placeholder="-1.2921"></div>
                <div class="form-group"><label>Longitude</label><input type="text" name="longitude" required placeholder="36.8219"></div>
                <div class="form-group"><label>Road name</label><input type="text" name="road_name" placeholder="Mombasa Road"></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2" placeholder="What should drivers know?"></textarea></div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-flag"></i> Save alert</button>
            </form>
        </div>
    </div>

    <div>
        <div class="page-toolbar">
            <h2><i class="fa-solid fa-flag"></i> Saved alerts <span style="font-weight:500;color:var(--muted);font-size:.85rem">({{ $reports->total() }})</span></h2>
            <form action="/admin/reports" method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center" data-turbo-frame="main-content" data-turbo-action="advance">
                <select name="status" onchange="this.form.submit()" style="padding:.4rem .6rem;border-radius:8px;border:1px solid var(--border);font-size:.8rem;font-family:inherit;">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Live</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Hidden</option>
                </select>
                <select name="type" onchange="this.form.submit()" style="padding:.4rem .6rem;border-radius:8px;border:1px solid var(--border);font-size:.8rem;font-family:inherit;">
                    <option value="">All types</option>
                    @foreach($types as $option)
                        <option value="{{ $option }}" {{ $type === $option ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                    @endforeach
                </select>
                <div class="search-box" style="max-width:220px;width:100%">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Road or reporter…">
                </div>
            </form>
        </div>

        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Alert</th>
                        <th>Reporter</th>
                        <th>Location</th>
                        <th>Votes</th>
                        <th>Status</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reports as $report)
                <tr>
                    <td>
                        <strong>{{ ucwords(str_replace('_', ' ', $report->type)) }}</strong>
                        <div style="font-size:.75rem;color:var(--muted);max-width:240px">{{ $report->description ?: ($report->road_name ?: 'No details') }}</div>
                    </td>
                    <td>{{ $report->user?->name ?? '—' }}</td>
                    <td>
                        <div>{{ $report->road_name ?? '—' }}</div>
                        <a href="https://www.openstreetmap.org/?mlat={{ $report->latitude }}&amp;mlon={{ $report->longitude }}#map=16/{{ $report->latitude }}/{{ $report->longitude }}" target="_blank" class="btn btn-outline btn-sm" style="margin-top:.35rem">
                            <i class="fa-solid fa-location-dot"></i> Map
                        </a>
                    </td>
                    <td>
                        <span class="badge badge-green">+{{ $report->confirmations_count }}</span>
                        <span class="badge badge-red">−{{ $report->dismissals_count }}</span>
                        <div style="font-size:.75rem;color:var(--muted);margin-top:.25rem">{{ $report->confidence }}% confidence</div>
                    </td>
                    <td>
                        @if($report->is_active)
                            <span class="badge badge-green">Live</span>
                        @else
                            <span class="badge badge-yellow">Hidden</span>
                        @endif
                        <div style="font-size:.75rem;color:var(--muted);margin-top:.25rem">{{ $report->status ?? '—' }}</div>
                    </td>
                    <td>{{ $report->created_at->format('M j, H:i') }}</td>
                    <td style="white-space:nowrap;display:flex;gap:.35rem">
                        @if($report->is_active)
                        <form class="inline" method="POST" action="/admin/reports/{{ $report->id }}/deactivate"
                              data-confirm="Drivers will no longer see this alert."
                              data-confirm-title="Hide alert?"
                              data-confirm-icon="warning"
                              data-confirm-button="Hide">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-outline btn-sm">Hide</button>
                        </form>
                        @else
                        <form class="inline" method="POST" action="/admin/reports/{{ $report->id }}/activate"
                              data-confirm="This alert will show to drivers again."
                              data-confirm-title="Make live?"
                              data-confirm-icon="question"
                              data-confirm-button="Make live">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-primary btn-sm">Live</button>
                        </form>
                        @endif
                        <form class="inline" method="POST" action="/admin/reports/{{ $report->id }}"
                              data-confirm="Permanently delete this saved alert?"
                              data-confirm-title="Delete alert?"
                              data-confirm-icon="warning"
                              data-confirm-button="Delete"
                              data-confirm-danger="true">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state"><i class="fa-solid fa-flag"></i>No road alerts saved yet. Reports from the app appear here.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $reports->links() }}</div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 1100px) {
    .alerts-layout { grid-template-columns: 1fr !important; }
}
</style>
@endpush
