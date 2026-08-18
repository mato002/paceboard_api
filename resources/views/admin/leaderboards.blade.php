@extends('admin.layout')

@section('title', 'Leaderboards — PaceBoard Admin')
@section('page_title', 'Leaderboards')
@section('page_subtitle', 'Current ranks and reset controls')

@section('content')
<div class="page-toolbar">
    <h2><i class="fa-solid fa-ranking-star"></i> Rankings</h2>
    <form action="/admin/leaderboards" method="GET" style="display:flex;gap:.5rem;flex-wrap:wrap" data-turbo-frame="main-content" data-turbo-action="advance">
        <select name="category" onchange="this.form.submit()" style="padding:.4rem .6rem;border-radius:8px;border:1px solid var(--border);font-size:.8rem;font-family:inherit;">
            @forelse($categories as $option)
                <option value="{{ $option }}" {{ $category === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
            @empty
                <option value="safety">Safety</option>
            @endforelse
        </select>
        <select name="period" onchange="this.form.submit()" style="padding:.4rem .6rem;border-radius:8px;border:1px solid var(--border);font-size:.8rem;font-family:inherit;">
            @forelse($periods as $option)
                <option value="{{ $option }}" {{ $period === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
            @empty
                <option value="monthly">Monthly</option>
            @endforelse
        </select>
    </form>
</div>

<div class="panel">
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Driver</th>
                <th>Category</th>
                <th>Period</th>
                <th>Score</th>
            </tr>
        </thead>
        <tbody>
        @forelse($entries as $entry)
        <tr>
            <td><strong>#{{ $entry->rank_position }}</strong></td>
            <td>{{ $entry->user?->name ?? '—' }}</td>
            <td>{{ $entry->category }}</td>
            <td>{{ $entry->period }}</td>
            <td>{{ $entry->score_value }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="empty-state"><i class="fa-solid fa-ranking-star"></i>No rankings for this filter</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="pagination-wrap">{{ $entries->links() }}</div>

<div class="panel" style="margin-top:1.5rem">
    <div class="panel-header">
        <div class="panel-header-left"><i class="fa-solid fa-rotate-left"></i> Reset leaderboards</div>
    </div>
    <div style="padding:1.25rem;display:flex;gap:.75rem;flex-wrap:wrap">
        <form method="POST" action="/admin/leaderboards/reset"
              data-confirm="Clear global leaderboard scores?"
              data-confirm-title="Reset global ranks?"
              data-confirm-icon="warning"
              data-confirm-button="Reset"
              data-confirm-danger="true">
            @csrf
            <input type="hidden" name="scope" value="global">
            <button class="btn btn-outline" type="submit">Reset global</button>
        </form>
        <form method="POST" action="/admin/leaderboards/reset"
              data-confirm="Clear per-route leaderboards?"
              data-confirm-title="Reset route ranks?"
              data-confirm-icon="warning"
              data-confirm-button="Reset"
              data-confirm-danger="true">
            @csrf
            <input type="hidden" name="scope" value="routes">
            <button class="btn btn-outline" type="submit">Reset routes</button>
        </form>
    </div>
</div>
@endsection
