@extends('admin.layout')

@section('title', 'Challenges — PaceBoard Admin')
@section('page_title', 'Challenges')
@section('page_subtitle', 'Create and monitor driving challenges')

@section('content')
<div class="challenges-grid" style="display:grid;grid-template-columns:1fr 1.3fr;gap:1.5rem;align-items:start;">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-plus-circle"></i> Create challenge</div>
        </div>
        <div style="padding:1.25rem;">
            <form method="POST" action="/admin/challenges"
                  data-confirm="Create this challenge and make it available to all drivers?"
                  data-confirm-title="Create challenge?"
                  data-confirm-icon="question"
                  data-confirm-button="Create challenge">
                @csrf
                <div class="form-group"><label>Title</label><input type="text" name="title" required placeholder="Weekend warrior"></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2" placeholder="Complete 3 weekend trips…"></textarea></div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="distance"><i class="fa-solid fa-road"></i> Distance (km)</option>
                        <option value="trips">Trips count</option>
                        <option value="night_drive">Night drives</option>
                        <option value="weekend">Weekend trips</option>
                        <option value="route">Route completion</option>
                    </select>
                </div>
                <div class="form-group"><label>Target value</label><input type="number" name="target_value" required min="1"></div>
                <div class="form-group"><label>Reward points</label><input type="number" name="reward_points" value="100" min="0"></div>
                <div class="form-group"><label>Ends at</label><input type="datetime-local" name="ends_at"></div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-trophy"></i> Create challenge</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-trophy"></i> Active challenges</div>
        </div>
        <table>
            <thead>
                <tr><th>Title</th><th>Type</th><th>Target</th><th>Participants</th><th>Ends</th></tr>
            </thead>
            <tbody>
            @forelse($challenges as $challenge)
            <tr>
                <td><strong>{{ $challenge->title }}</strong></td>
                <td><span class="badge badge-yellow">{{ $challenge->type }}</span></td>
                <td>{{ $challenge->target_value }}</td>
                <td><i class="fa-solid fa-users" style="color:var(--muted);margin-right:.3rem"></i>{{ $challenge->participants_count }}</td>
                <td>{{ $challenge->ends_at?->format('M j, Y') ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty-state"><i class="fa-solid fa-trophy"></i>No challenges yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination-wrap">{{ $challenges->links() }}</div>
@endsection
