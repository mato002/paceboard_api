<turbo-frame id="recent-reports"
    @if(session('status')) data-flash-success="{{ session('status') }}" @endif
>
    <div class="panel">
        <div class="panel-header">
            <div class="panel-header-left"><i class="fa-solid fa-flag"></i> Recent reports</div>
            <a href="/admin/reports" class="panel-action" data-turbo-frame="main-content" data-turbo-action="advance">View all</a>
        </div>
        <table>
            <thead><tr><th>Type</th><th>Road</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($recentReports as $report)
            <tr>
                <td><i class="fa-solid fa-location-dot" style="color:var(--muted);margin-right:.35rem"></i>{{ $report->type }}</td>
                <td>{{ $report->road_name ?? '—' }}</td>
                <td>
                    @if($report->is_active)
                        <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:.4rem"></i> Active</span>
                    @else
                        <span class="badge badge-yellow">Inactive</span>
                    @endif
                </td>
                <td>
                    @if($report->is_active)
                    <form class="inline" method="POST" action="/admin/reports/{{ $report->id }}/deactivate"
                          data-turbo-frame="recent-reports"
                          data-confirm="This report will be marked inactive and hidden from drivers."
                          data-confirm-title="Deactivate report?"
                          data-confirm-icon="warning"
                          data-confirm-button="Yes, deactivate">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-ban"></i> Deactivate</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="empty-state"><i class="fa-solid fa-flag"></i>No reports</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</turbo-frame>
