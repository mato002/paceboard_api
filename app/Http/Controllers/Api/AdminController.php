<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\RouteLeaderboard;
use App\Models\SosAlert;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\FcmService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats()
    {
        $avgSpeed = \App\Models\Trip::whereNotNull('ended_at')->avg('average_speed');

        return response()->json([
            'users' => User::count(),
            'trips' => \App\Models\Trip::count(),
            'active_reports' => \App\Models\CommunityReport::where('is_active', true)->count(),
            'trips_today' => \App\Models\Trip::whereDate('created_at', today())->count(),
            'total_distance_km' => round((float) \App\Models\Trip::sum('distance'), 2),
            'active_drivers' => User::whereHas('trips', fn ($q) => $q->whereNull('ended_at'))->count(),
            'popular_routes' => \App\Models\Route::where('is_popular', true)->count(),
            'active_challenges' => \App\Models\Challenge::where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })->count(),
            'average_speed_kmh' => round((float) $avgSpeed, 1),
            'active_sos_alerts' => SosAlert::where('status', 'active')->count(),
        ]);
    }

    public function users()
    {
        return response()->json(
            User::with('roles:id,name,slug')
                ->select('id', 'name', 'email', 'is_admin', 'driver_status', 'phone_verified_at', 'total_distance', 'driving_hours', 'created_at')
                ->latest()
                ->paginate(20)
        );
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $request->validate([
            'driver_status' => 'required|in:active,suspended,verified,pending',
        ]);

        $user->update(['driver_status' => $request->driver_status]);
        ActivityLog::record('user_status_updated', $request->user()->id, [
            'target_user_id' => $user->id,
            'status' => $request->driver_status,
        ]);

        return response()->json(['message' => 'User status updated', 'user' => $user]);
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|exists:roles,slug']);

        $role = Role::where('slug', $request->role)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);

        if ($role->slug === 'admin') {
            $user->update(['is_admin' => true]);
        }

        return response()->json(['message' => 'Role assigned']);
    }

    public function roles()
    {
        return response()->json(Role::all());
    }

    public function trips()
    {
        return response()->json(
            \App\Models\Trip::with(['user:id,name,email', 'route:id,name', 'drivingAnalysis'])
                ->latest()
                ->paginate(20)
        );
    }

    public function deleteTrip(\App\Models\Trip $trip)
    {
        $user = $trip->user;

        if ($trip->analytics_processed_at) {
            $user?->decrement('total_distance', (float) ($trip->analytics_distance_applied ?? $trip->distance));
            $user?->decrement('driving_hours', round(($trip->analytics_moving_seconds_applied ?? 0) / 3600, 4));
        }

        if ($trip->route_id) {
            \App\Models\Route::whereKey($trip->route_id)->decrement('total_trips');
        }

        ActivityLog::record('trip_deleted', request()->user()?->id, ['trip_id' => $trip->id]);
        $trip->delete();

        if ($user) {
            app(\App\Services\LeaderboardService::class)->updateForUser($user->fresh());
        }

        return response()->json(['message' => 'Trip deleted']);
    }

    public function routes()
    {
        return response()->json(
            \App\Models\Route::withCount('trips')->orderByDesc('total_trips')->paginate(20)
        );
    }

    public function challenges()
    {
        return response()->json(
            \App\Models\Challenge::withCount('participants')->latest()->paginate(20)
        );
    }

    public function createChallenge()
    {
        $validated = request()->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:distance,trips,night_drive,weekend,route',
            'target_value' => 'required|integer|min:1',
            'reward_points' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $challenge = \App\Models\Challenge::create($validated);

        return response()->json(['message' => 'Challenge created', 'challenge' => $challenge], 201);
    }

    public function settings(SettingsService $settings)
    {
        return response()->json($settings->all());
    }

    public function updateSettings(Request $request, SettingsService $settings)
    {
        $validated = $request->validate([
            'speed_limit_kmh' => 'nullable|integer|min:30|max:200',
            'ranking_min_score' => 'nullable|integer|min:0|max:100',
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string|max:500',
            'fuel_consumption_per_100km' => 'nullable|numeric|min:1|max:50',
            'map_provider' => 'nullable|in:openstreetmap',
            'ranking_rules' => 'nullable|array',
        ]);

        foreach ($validated as $key => $value) {
            if ($value === null) {
                continue;
            }
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_array($value) => 'json',
                is_int($value) => 'integer',
                is_float($value) => 'float',
                default => 'string',
            };
            $settings->set($key, $value, $type, 'app');
        }

        ActivityLog::record('settings_updated', $request->user()->id, $validated);

        return response()->json(['message' => 'Settings updated', 'settings' => $settings->all()]);
    }

    public function resetLeaderboards(Request $request)
    {
        $request->validate([
            'scope' => 'required|in:global,routes,all',
        ]);

        if (in_array($request->scope, ['global', 'all'])) {
            Leaderboard::truncate();
        }

        if (in_array($request->scope, ['routes', 'all'])) {
            RouteLeaderboard::truncate();
        }

        ActivityLog::record('leaderboards_reset', $request->user()->id, ['scope' => $request->scope]);

        return response()->json(['message' => 'Leaderboards reset successfully']);
    }

    public function broadcast(Request $request, FcmService $fcm)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        $users = User::all();
        $tokens = $users->whereNotNull('fcm_token')->pluck('fcm_token')->all();
        $sent = $fcm->broadcast($tokens, $request->title, $request->body);

        foreach ($users as $recipient) {
            UserNotification::create([
                'user_id' => $recipient->id,
                'type' => 'broadcast',
                'title' => $request->title,
                'body' => $request->body,
            ]);
        }

        ActivityLog::record('broadcast_sent', $request->user()->id, [
            'title' => $request->title,
            'devices' => $sent,
        ]);

        return response()->json(['message' => "Broadcast sent to {$sent} devices"]);
    }

    public function sosAlerts()
    {
        return response()->json(
            SosAlert::with('user:id,name,phone')->where('status', 'active')->latest()->paginate(20)
        );
    }

    public function resolveSos(SosAlert $alert)
    {
        $alert->update(['status' => 'resolved', 'resolved_at' => now()]);

        return response()->json(['message' => 'SOS alert resolved']);
    }

    public function activityLogs()
    {
        return response()->json(
            ActivityLog::with('user:id,name')->latest()->paginate(50)
        );
    }

    public function deactivateReport(\App\Models\CommunityReport $report)
    {
        $report->update(['is_active' => false]);

        return response()->json(['message' => 'Report deactivated']);
    }
}
