@extends('admin.layout')

@section('title', 'Vehicles — PaceBoard Admin')
@section('page_title', 'Vehicles')
@section('page_subtitle', 'Cars registered by drivers')

@section('content')
<div class="page-toolbar">
    <h2><i class="fa-solid fa-car"></i> Vehicles <span style="font-weight:500;color:var(--muted);font-size:.85rem">({{ $vehicles->total() }})</span></h2>
</div>
<div class="panel">
    <table>
        <thead>
            <tr>
                <th>Owner</th>
                <th>Vehicle</th>
                <th>Registration</th>
                <th>Fuel</th>
                <th>Mileage</th>
                <th>Added</th>
            </tr>
        </thead>
        <tbody>
        @forelse($vehicles as $vehicle)
        <tr>
            <td>
                <strong>{{ $vehicle->user?->name ?? '—' }}</strong>
                <div style="font-size:.75rem;color:var(--muted)">{{ $vehicle->user?->email }}</div>
            </td>
            <td>{{ trim(($vehicle->year ? $vehicle->year.' ' : '').($vehicle->manufacturer ?? '').' '.($vehicle->model ?? '')) ?: '—' }}</td>
            <td>{{ $vehicle->registration_number ?? '—' }}</td>
            <td>{{ $vehicle->fuel_type ?? '—' }}</td>
            <td>{{ $vehicle->mileage ? number_format($vehicle->mileage).' km' : '—' }}</td>
            <td>{{ $vehicle->created_at->format('M j, Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="empty-state"><i class="fa-solid fa-car"></i>No vehicles registered</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="pagination-wrap">{{ $vehicles->links() }}</div>
@endsection
