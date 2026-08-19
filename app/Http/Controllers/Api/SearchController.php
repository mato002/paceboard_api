<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:drivers,routes,trips,cities,all',
        ]);

        $q = $request->q;
        $type = $request->type ?? 'all';
        $results = [];

        if (in_array($type, ['drivers', 'all'])) {
            $followingIds = $request->user()->following()->pluck('users.id')->all();
            $results['drivers'] = User::where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->select('id', 'name', 'country', 'total_distance', 'driving_hours', 'avatar_path', 'updated_at')
                ->limit(10)
                ->get()
                ->map(fn (User $driver) => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'country' => $driver->country,
                    'total_distance' => $driver->total_distance,
                    'driving_hours' => $driver->driving_hours,
                    'avatar_url' => $driver->avatar_url,
                    'is_following' => in_array($driver->id, $followingIds, true),
                    'is_me' => $driver->id === $request->user()->id,
                ]);
        }

        if (in_array($type, ['routes', 'all'])) {
            $results['routes'] = Route::where('name', 'like', "%{$q}%")
                ->orWhere('start_city', 'like', "%{$q}%")
                ->orWhere('end_city', 'like', "%{$q}%")
                ->limit(10)
                ->get();
        }

        if (in_array($type, ['trips', 'all'])) {
            $results['trips'] = Trip::where('user_id', $request->user()->id)
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('start_location', 'like', "%{$q}%")
                        ->orWhere('destination', 'like', "%{$q}%")
                        ->orWhere('start_city', 'like', "%{$q}%")
                        ->orWhere('end_city', 'like', "%{$q}%");
                })
                ->with('route:id,name')
                ->limit(10)
                ->get();
        }

        if (in_array($type, ['cities', 'all'])) {
            $results['cities'] = collect(config('kenya_cities', []))
                ->filter(fn ($coords, $city) => stripos((string) $city, $q) !== false)
                ->take(10)
                ->map(fn ($coords, $city) => [
                    'name' => $city,
                    'lat' => $coords['lat'] ?? null,
                    'lng' => $coords['lng'] ?? null,
                ])
                ->values();
        }

        return response()->json(['query' => $q, 'results' => $results]);
    }
}
