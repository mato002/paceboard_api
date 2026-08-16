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
            $results['drivers'] = User::where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->select('id', 'name', 'country', 'total_distance', 'driving_hours')
                ->limit(10)
                ->get();
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
            $cities = collect(config('kenya_cities', []))->keys()
                ->filter(fn ($city) => stripos($city, $q) !== false)
                ->values()
                ->take(10);
            $results['cities'] = $cities;
        }

        return response()->json(['query' => $q, 'results' => $results]);
    }
}
