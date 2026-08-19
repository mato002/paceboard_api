@extends('admin.layout')

@section('title', 'Users — PaceBoard Admin')
@section('page_title', 'Users')
@section('page_subtitle', 'Manage drivers, roles, and account status')

@section('content')
<div class="page-toolbar">
    <h2><i class="fa-solid fa-users"></i> All users <span style="font-weight:500;color:var(--muted);font-size:.85rem">({{ $users->total() }})</span></h2>
    <form action="/admin/users" method="GET" class="search-box" style="max-width:320px;width:100%" data-turbo-frame="main-content" data-turbo-action="advance">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="q" value="{{ $search ?? request('q') }}" placeholder="Filter users…">
    </form>
</div>

<div class="panel">
    <div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th>Driver</th>
                <th>Contact</th>
                <th>Distance</th>
                <th>Status</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($users as $user)
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:.65rem">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <strong>{{ $user->name }}</strong>
                </div>
            </td>
            <td>
                <div>{{ $user->email }}</div>
                <div style="font-size:.75rem;color:var(--muted)">{{ $user->phone ?? '—' }}</div>
            </td>
            <td>{{ number_format($user->total_distance) }} km</td>
            <td>
                <form method="POST" action="/admin/users/{{ $user->id }}/status" class="inline" data-skip-loader="true">
                    @csrf @method('PATCH')
                    <select name="driver_status" onchange="this.form.submit()" style="padding:.35rem .55rem;border-radius:8px;border:1px solid var(--border);font-size:.8rem;font-family:inherit;">
                        @foreach(['active','verified','pending','suspended'] as $status)
                        <option value="{{ $status }}" {{ ($user->driver_status ?? 'active') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </form>
            </td>
            <td>
                @if($user->is_admin)
                    <span class="badge badge-green"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                @else
                    <span class="badge badge-yellow">Driver</span>
                @endif
            </td>
            <td>{{ $user->created_at->format('M j, Y') }}</td>
            <td>
                @if(!$user->is_admin)
                <form class="inline" method="POST" action="/admin/users/{{ $user->id }}/make-admin"
                      data-confirm="Grant admin privileges to {{ $user->name }}? They will have full access to this console."
                      data-confirm-title="Promote to admin?"
                      data-confirm-icon="question"
                      data-confirm-button="Yes, make admin">@csrf
                    <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-user-shield"></i> Make admin</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="empty-state"><i class="fa-solid fa-users"></i>No users found</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="pagination-wrap">{{ $users->links() }}</div>
@endsection
