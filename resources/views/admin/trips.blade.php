@extends('admin.layout')

@section('title', 'Trips — PaceBoard Admin')
@section('page_title', 'Trips')
@section('page_subtitle', 'View and manage recorded driving trips')

@section('content')
<div class="page-toolbar">
    <h2><i class="fa-solid fa-route"></i> All trips <span style="font-weight:500;color:var(--muted);font-size:.85rem">({{ $trips->total() }})</span></h2>
</div>

<div class="panel">
    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>Trip name</th>
                <th>Route</th>
                <th>Distance</th>
                <th>Duration</th>
                <th>Score</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($trips as $trip)
        <tr>
            <td><i class="fa-solid fa-user" style="color:var(--muted);margin-right:.4rem"></i>{{ $trip->user?->name ?? '—' }}</td>
            <td><strong>{{ $trip->name ?? '—' }}</strong></td>
            <td>{{ $trip->route?->name ?? ($trip->start_city && $trip->end_city ? $trip->start_city.' → '.$trip->end_city : '—') }}</td>
            <td>{{ $trip->distance }} km</td>
            <td>{{ $trip->duration_seconds ? round($trip->duration_seconds/60).' min' : '—' }}</td>
            <td><span class="badge badge-green">{{ $trip->score }}</span></td>
            <td>{{ $trip->created_at->format('M j, Y H:i') }}</td>
            <td>
                <form class="inline" method="POST" action="/admin/trips/{{ $trip->id }}"
                      data-confirm="This trip will be permanently deleted from the system."
                      data-confirm-title="Delete trip?"
                      data-confirm-icon="warning"
                      data-confirm-button="Yes, delete"
                      data-confirm-danger="true">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="empty-state"><i class="fa-solid fa-route"></i>No trips recorded</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="pagination-wrap">{{ $trips->links() }}</div>
@endsection
