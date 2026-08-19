<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Challenge;
use App\Models\CommunityReport;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Route as DrivingRoute;
use App\Models\RouteLeaderboard;
use App\Models\SosAlert;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Vehicle;
use App\Services\FcmService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAdminController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Invalid credentials']);
        }

        if (! Auth::user()->is_admin) {
            Auth::logout();

            return back()->withErrors(['email' => 'Admin access required']);
        }

        $request->session()->regenerate();

        return redirect('/admin/dashboard');
    }

    public function dashboard()
    {
        return view('admin.dashboard', [
            'stats' => $this->stats(),
            'recentUsers' => User::latest()->take(10)->get(),
            'recentReports' => CommunityReport::latest()->take(10)->get(),
            'recentTrips' => Trip::with('user:id,name')->latest()->take(10)->get(),
        ]);
    }

    public function dashboardPanel(string $panel)
    {
        return match ($panel) {
            'users' => view('admin.panels.recent-users', [
                'recentUsers' => User::latest()->take(10)->get(),
            ]),
            'reports' => view('admin.panels.recent-reports', [
                'recentReports' => CommunityReport::latest()->take(10)->get(),
            ]),
            'trips' => view('admin.panels.recent-trips', [
                'recentTrips' => Trip::with('user:id,name')->latest()->take(10)->get(),
            ]),
            default => abort(404),
        };
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('admin.users', [
            'users' => $query->latest()->paginate(25)->withQueryString(),
            'search' => $search ?? '',
        ]);
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $request->validate(['driver_status' => 'required|in:active,suspended,verified,pending']);
        $user->update(['driver_status' => $request->driver_status]);
        ActivityLog::record('user_status_updated', Auth::id(), ['user_id' => $user->id, 'status' => $request->driver_status]);

        return back()->with('status', 'User status updated');
    }

    public function makeAdmin(User $user)
    {
        $user->update(['is_admin' => true]);
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        return back()->with('status', "{$user->name} is now an admin");
    }

    public function trips()
    {
        return view('admin.trips', [
            'trips' => Trip::with(['user:id,name', 'route:id,name,start_city,end_city'])->latest()->paginate(25),
        ]);
    }

    public function deleteTrip(Trip $trip)
    {
        $user = $trip->user;

        if ($trip->analytics_processed_at) {
            $user?->decrement('total_distance', (float) ($trip->analytics_distance_applied ?? $trip->distance));
            $user?->decrement('driving_hours', round(($trip->analytics_moving_seconds_applied ?? 0) / 3600, 4));
        }

        if ($trip->route_id) {
            DrivingRoute::whereKey($trip->route_id)->decrement('total_trips');
        }

        ActivityLog::record('trip_deleted', Auth::id(), ['trip_id' => $trip->id]);
        $trip->delete();

        if ($user) {
            app(\App\Services\LeaderboardService::class)->updateForUser($user->fresh());
        }

        return back()->with('status', 'Trip deleted');
    }

    public function settings(SettingsService $settings)
    {
        return view('admin.settings', ['settings' => $settings->all()]);
    }

    public function updateSettings(Request $request, SettingsService $settings)
    {
        $settings->set('speed_limit_kmh', (int) $request->input('speed_limit_kmh', 80), 'integer', 'app');
        $settings->set('ranking_min_score', (int) $request->input('ranking_min_score', 60), 'integer', 'app');
        $settings->set('fuel_consumption_per_100km', (float) $request->input('fuel_consumption_per_100km', 8.5), 'float', 'app');
        $settings->set('map_provider', $request->input('map_provider', 'openstreetmap'), 'string', 'app');
        $settings->set('maintenance_mode', $request->boolean('maintenance_mode'), 'boolean', 'app');
        $settings->set('maintenance_message', $request->input('maintenance_message', ''), 'string', 'app');

        ActivityLog::record('settings_updated', Auth::id());

        return back()->with('status', 'Settings saved');
    }

    public function resetLeaderboards(Request $request)
    {
        $scope = $request->input('scope', 'global');

        if (in_array($scope, ['global', 'all'])) {
            Leaderboard::truncate();
        }
        if (in_array($scope, ['routes', 'all'])) {
            RouteLeaderboard::truncate();
        }

        ActivityLog::record('leaderboards_reset', Auth::id(), ['scope' => $scope]);

        return back()->with('status', 'Leaderboards reset');
    }

    public function broadcast(Request $request, FcmService $fcm)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        $tokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->all();
        $sent = $fcm->broadcast($tokens, $request->title, $request->body);

        foreach (User::all() as $recipient) {
            UserNotification::create([
                'user_id' => $recipient->id,
                'type' => 'broadcast',
                'title' => $request->title,
                'body' => $request->body,
            ]);
        }

        ActivityLog::record('broadcast_sent', Auth::id(), ['devices' => $sent]);

        return back()->with('status', "Broadcast sent to {$sent} devices");
    }

    public function reports(Request $request)
    {
        $query = CommunityReport::with('user:id,name,email');
        $status = $request->string('status')->toString() ?: 'all';
        $type = $request->string('type')->toString();
        $search = $request->string('q')->trim()->toString();

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('road_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            });
        }

        return view('admin.reports', [
            'reports' => $query->latest()->paginate(25)->withQueryString(),
            'status' => $status,
            'type' => $type,
            'search' => $search,
            'types' => CommunityReport::query()->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function createReport(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:speed_camera,accident,pothole,traffic,police,hazard,road_closure,construction,fuel_price,parking,restaurant,breakdown,flooding,debris,school_zone',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'road_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        CommunityReport::create([
            ...$validated,
            'user_id' => Auth::id(),
            'verification_score' => 0,
            'confirmations_count' => 0,
            'dismissals_count' => 0,
            'status' => 'active',
            'is_active' => true,
            'last_confirmed_at' => now(),
            'expires_at' => CommunityReport::expiryForType($validated['type']),
        ]);

        ActivityLog::record('report_created', Auth::id(), ['type' => $validated['type']]);

        return back()->with('status', 'Road alert created');
    }

    public function activateReport(CommunityReport $report)
    {
        $report->update([
            'is_active' => true,
            'status' => 'active',
            'expires_at' => CommunityReport::expiryForType($report->type),
        ]);
        ActivityLog::record('report_activated', Auth::id(), ['report_id' => $report->id]);

        return back()->with('status', 'Alert is live for drivers');
    }

    public function deleteReport(CommunityReport $report)
    {
        ActivityLog::record('report_deleted', Auth::id(), ['report_id' => $report->id, 'type' => $report->type]);
        $report->delete();

        return back()->with('status', 'Alert deleted');
    }

    public function sosAlerts(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'active';
        $query = SosAlert::with('user:id,name,phone');

        if (in_array($status, ['active', 'resolved'], true)) {
            $query->where('status', $status);
        }

        return view('admin.sos', [
            'alerts' => $query->latest()->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function resolveSos(SosAlert $alert)
    {
        $alert->update(['status' => 'resolved', 'resolved_at' => now()]);

        return back()->with('status', 'SOS alert resolved');
    }

    public function challenges()
    {
        return view('admin.challenges', [
            'challenges' => Challenge::withCount('participants')->latest()->paginate(25),
        ]);
    }

    public function createChallenge(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:distance,trips,night_drive,weekend,route',
            'target_value' => 'required|integer|min:1',
            'reward_points' => 'nullable|integer|min:0',
            'ends_at' => 'nullable|date',
        ]);

        Challenge::create([
            ...$validated,
            'starts_at' => now(),
            'reward_points' => $validated['reward_points'] ?? 0,
        ]);

        return back()->with('status', 'Challenge created');
    }

    public function routes()
    {
        return view('admin.routes', [
            'routes' => DrivingRoute::withCount('trips')->latest()->paginate(25),
        ]);
    }

    public function toggleRoutePopular(DrivingRoute $drivingRoute)
    {
        $drivingRoute->update(['is_popular' => ! $drivingRoute->is_popular]);
        ActivityLog::record('route_popular_toggled', Auth::id(), ['route_id' => $drivingRoute->id, 'is_popular' => $drivingRoute->is_popular]);

        return back()->with('status', $drivingRoute->is_popular ? 'Route marked popular' : 'Route unmarked popular');
    }

    public function vehicles()
    {
        return view('admin.vehicles', [
            'vehicles' => Vehicle::with('user:id,name,email')
                ->withCount('trips')
                ->latest()
                ->paginate(25),
        ]);
    }

    public function leaderboards(Request $request)
    {
        $category = $request->string('category')->toString() ?: 'safety';
        $period = $request->string('period')->toString() ?: 'monthly';

        return view('admin.leaderboards', [
            'entries' => Leaderboard::with('user:id,name,email')
                ->where('category', $category)
                ->where('period', $period)
                ->orderBy('rank_position')
                ->paginate(50)
                ->withQueryString(),
            'category' => $category,
            'period' => $period,
            'categories' => Leaderboard::query()->distinct()->orderBy('category')->pluck('category'),
            'periods' => Leaderboard::query()->distinct()->orderBy('period')->pluck('period'),
        ]);
    }

    public function activityLogs()
    {
        return view('admin.activity', [
            'logs' => ActivityLog::with('user:id,name')->latest()->paginate(40),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }

    public function deactivateReport(Request $request, CommunityReport $report)
    {
        $report->update(['is_active' => false, 'status' => 'archived']);

        if ($request->header('Turbo-Frame') === 'recent-reports') {
            return view('admin.panels.recent-reports', [
                'recentReports' => CommunityReport::latest()->take(10)->get(),
            ])->with('status', 'Report deactivated');
        }

        return back()->with('status', 'Report deactivated');
    }

    private function stats(): array
    {
        return [
            'users' => User::count(),
            'trips' => Trip::count(),
            'reports' => CommunityReport::where('is_active', true)->count(),
            'trips_today' => Trip::whereDate('created_at', today())->count(),
            'total_distance' => round((float) Trip::sum('distance'), 1),
            'active_sos' => SosAlert::where('status', 'active')->count(),
        ];
    }
}
