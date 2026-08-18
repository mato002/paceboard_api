@extends('admin.layout')

@section('title', 'SOS Alerts — PaceBoard Admin')
@section('page_title', 'SOS Alerts')
@section('page_subtitle', 'Emergency alerts from drivers — active and resolved history')

@section('content')
<div class="page-toolbar">
    <h2><i class="fa-solid fa-triangle-exclamation" style="color:var(--danger)"></i> SOS alerts <span style="font-weight:500;color:var(--muted);font-size:.85rem">({{ $alerts->total() }})</span></h2>
    <form action="/admin/sos" method="GET" data-turbo-frame="main-content" data-turbo-action="advance">
        <select name="status" onchange="this.form.submit()" style="padding:.4rem .6rem;border-radius:8px;border:1px solid var(--border);font-size:.8rem;font-family:inherit;">
            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
            <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
        </select>
    </form>
</div>

@if($alerts->isEmpty())
<div class="panel">
    <div class="empty-state" style="padding:4rem 2rem">
        <i class="fa-solid fa-shield-halved" style="color:var(--success);opacity:.6"></i>
        <h3 style="margin:.5rem 0;color:var(--text)">{{ $status === 'active' ? 'All clear' : 'No SOS alerts' }}</h3>
        <p>{{ $status === 'active' ? 'No active SOS alerts at this time.' : 'Nothing recorded for this filter.' }}</p>
    </div>
</div>
@else
<div class="panel">
    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>Phone</th>
                <th>Location</th>
                <th>Message</th>
                <th>Status</th>
                <th>Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($alerts as $alert)
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:.5rem">
                    @if($alert->status === 'active')
                    <span style="width:8px;height:8px;background:var(--danger);border-radius:50%;animation:pulse 2s infinite"></span>
                    @endif
                    <strong>{{ $alert->user?->name ?? '—' }}</strong>
                </div>
            </td>
            <td><i class="fa-solid fa-phone" style="color:var(--muted);margin-right:.35rem"></i>{{ $alert->user?->phone ?? '—' }}</td>
            <td>
                <a href="https://www.openstreetmap.org/?mlat={{ $alert->latitude }}&amp;mlon={{ $alert->longitude }}#map=16/{{ $alert->latitude }}/{{ $alert->longitude }}" target="_blank" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-location-dot"></i> View map
                </a>
            </td>
            <td>{{ $alert->message ?? '—' }}</td>
            <td>
                @if($alert->status === 'active')
                    <span class="badge badge-red">Active</span>
                @else
                    <span class="badge badge-green">Resolved</span>
                @endif
            </td>
            <td>{{ $alert->created_at->diffForHumans() }}</td>
            <td>
                @if($alert->status === 'active')
                <form class="inline" method="POST" action="/admin/sos/{{ $alert->id }}/resolve"
                      data-confirm="Mark this SOS alert as resolved? The driver will no longer appear in the active alerts list."
                      data-confirm-title="Resolve SOS alert?"
                      data-confirm-icon="question"
                      data-confirm-button="Yes, resolve">@csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Resolve</button>
                </form>
                @else
                    {{ $alert->resolved_at?->format('M j, H:i') ?? '—' }}
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="pagination-wrap">{{ $alerts->links() }}</div>
@endif
@endsection
