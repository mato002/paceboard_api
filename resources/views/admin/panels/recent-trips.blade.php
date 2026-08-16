<turbo-frame id="recent-trips">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-route"></i> Recent trips</div>
            <a href="/admin/trips" class="panel-action" data-turbo-frame="main-content" data-turbo-action="advance">View all <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <table>
            <thead><tr><th>Driver</th><th>Trip</th><th>Distance</th><th>Score</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($recentTrips as $trip)
            <tr>
                <td>{{ $trip->user?->name ?? '—' }}</td>
                <td>{{ $trip->name ?? '—' }}</td>
                <td>{{ $trip->distance }} km</td>
                <td><span class="badge badge-green">{{ $trip->score }}</span></td>
                <td>{{ $trip->created_at->format('M j, Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty-state"><i class="fa-solid fa-route"></i>No trips recorded</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</turbo-frame>
