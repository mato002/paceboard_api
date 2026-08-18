@extends('admin.layout')

@section('title', 'Routes — PaceBoard Admin')
@section('page_title', 'Routes')
@section('page_subtitle', 'Popular corridors built from completed trips')

@section('content')
<div class="page-toolbar">
    <h2><i class="fa-solid fa-map"></i> Routes <span style="font-weight:500;color:var(--muted);font-size:.85rem">({{ $routes->total() }})</span></h2>
</div>
<div class="panel">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Corridor</th>
                <th>Trips</th>
                <th>Popular</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($routes as $route)
        <tr>
            <td><strong>{{ $route->name }}</strong></td>
            <td>{{ $route->start_city }} → {{ $route->end_city }}</td>
            <td>{{ number_format($route->trips_count ?? $route->total_trips) }}</td>
            <td>
                @if($route->is_popular)
                    <span class="badge badge-green">Popular</span>
                @else
                    <span class="badge badge-yellow">Standard</span>
                @endif
            </td>
            <td>
                <form class="inline" method="POST" action="/admin/routes/{{ $route->id }}/popular">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline btn-sm">
                        {{ $route->is_popular ? 'Unmark popular' : 'Mark popular' }}
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="empty-state"><i class="fa-solid fa-map"></i>No routes yet. They appear after drivers complete trips.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="pagination-wrap">{{ $routes->links() }}</div>
@endsection
