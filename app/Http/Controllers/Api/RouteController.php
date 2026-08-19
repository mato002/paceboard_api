<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\RouteLeaderboard;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'filter' => 'nullable|in:popular,trending,recent',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = Route::query()->withCount('trips');

        match ($request->filter) {
            'popular' => $query->where('is_popular', true)->orderByDesc('total_trips'),
            'trending' => $query->where('updated_at', '>=', now()->subDays(7))->orderByDesc('total_trips'),
            default => $query->orderByDesc('total_trips'),
        };

        $limit = (int) ($request->limit ?? 20);

        return response()->json($query->paginate($limit));
    }

    public function show(Route $route)
    {
        $route->loadCount('trips');

        return response()->json(['route' => $route]);
    }

    public function leaderboard(Request $request, Route $route)
    {
        $request->validate([
            'category' => 'required|in:fastest,distance,score,trips',
        ]);

        $ascending = $request->category === 'fastest';

        $query = RouteLeaderboard::with('user:id,name,country')
            ->where('route_id', $route->id)
            ->where('category', $request->category);

        $entries = ($ascending ? $query->orderBy('score_value') : $query->orderByDesc('score_value'))
            ->take(100)
            ->get()
            ->values()
            ->map(function ($entry, $index) {
                $entry->rank_position = $entry->rank_position ?? ($index + 1);

                return $entry;
            });

        $myEntry = RouteLeaderboard::where('route_id', $route->id)
            ->where('user_id', $request->user()->id)
            ->where('category', $request->category)
            ->first();

        return response()->json([
            'route' => $route->only(['id', 'name', 'start_city', 'end_city']),
            'entries' => $entries,
            'my_rank' => $myEntry?->rank_position,
            'my_score' => $myEntry ? (float) $myEntry->score_value : null,
        ]);
    }
}
