<turbo-frame id="recent-users">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-users"></i> Recent users</div>
            <a href="/admin/users" class="panel-action" data-turbo-frame="main-content" data-turbo-action="advance">View all <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th></tr></thead>
            <tbody>
            @forelse($recentUsers as $user)
            <tr>
                <td><strong>{{ $user->name }}</strong></td>
                <td>{{ $user->email }}</td>
                <td><span class="badge badge-{{ $user->driver_status === 'active' ? 'green' : 'yellow' }}">{{ $user->driver_status ?? 'active' }}</span></td>
                <td>{{ $user->created_at->format('M j, Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="empty-state"><i class="fa-solid fa-users"></i>No users yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</turbo-frame>
