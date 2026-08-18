@extends('admin.layout')

@section('title', 'Activity — PaceBoard Admin')
@section('page_title', 'Activity Log')
@section('page_subtitle', 'Admin actions recorded on this console')

@section('content')
<div class="page-toolbar">
    <h2><i class="fa-solid fa-clipboard-list"></i> Recent activity</h2>
</div>
<div class="panel">
    <table>
        <thead>
            <tr>
                <th>When</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Details</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
        <tr>
            <td>{{ $log->created_at->format('M j, H:i') }}</td>
            <td>{{ $log->user?->name ?? '—' }}</td>
            <td><span class="badge badge-yellow">{{ str_replace('_', ' ', $log->action) }}</span></td>
            <td style="font-size:.8rem;color:var(--muted);max-width:360px">{{ $log->properties ? json_encode($log->properties) : '—' }}</td>
            <td>{{ $log->ip_address ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="empty-state"><i class="fa-solid fa-clipboard-list"></i>No admin activity yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="pagination-wrap">{{ $logs->links() }}</div>
@endsection
