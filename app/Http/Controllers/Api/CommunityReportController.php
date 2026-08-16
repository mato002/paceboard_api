<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommunityReportController extends Controller
{
    public function index(Request $request)
    {
        $query = CommunityReport::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($request->has(['sw_lat', 'sw_lng', 'ne_lat', 'ne_lng'])) {
            $query->whereBetween('latitude', [$request->sw_lat, $request->ne_lat])
                ->whereBetween('longitude', [$request->sw_lng, $request->ne_lng]);
        }

        return response()->json($query->latest()->limit(200)->get());
    }

    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1|max:50',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $radiusKm = (float) $request->input('radius_km', 5);
        $limit = (int) $request->input('limit', 50);

        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / max(cos(deg2rad($lat)), 0.01) / 111.0;

        $reports = CommunityReport::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->latest()
            ->limit(300)
            ->get()
            ->map(function (CommunityReport $report) use ($lat, $lng) {
                $distanceKm = $this->haversineKm($lat, $lng, (float) $report->latitude, (float) $report->longitude);
                $payload = $report->toArray();
                $payload['distance_km'] = round($distanceKm, 3);

                return $payload;
            })
            ->filter(fn (array $report) => $report['distance_km'] <= $radiusKm)
            ->sortBy('distance_km')
            ->take($limit)
            ->values();

        return response()->json($reports);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:speed_camera,accident,pothole,traffic,police,hazard,road_closure,construction,fuel_price,parking,restaurant,breakdown',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'road_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('reports', 'public');
        }

        $report = $request->user()->communityReports()->create([
            'type' => $request->type,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'road_name' => $request->road_name,
            'description' => $request->description,
            'photo_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
            'expires_at' => now()->addHours(2),
        ]);

        return response()->json([
            'message' => 'Report submitted successfully',
            'report' => $report,
        ], 201);
    }

    public function verify(Request $request, CommunityReport $report)
    {
        return $this->vote($request, $report, 'up');
    }

    public function dispute(Request $request, CommunityReport $report)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        return $this->vote($request, $report, 'down');
    }

    private function vote(Request $request, CommunityReport $report, string $vote): \Illuminate\Http\JsonResponse
    {
        $existing = DB::table('report_verifications')
            ->where('user_id', $request->user()->id)
            ->where('community_report_id', $report->id)
            ->first();

        if ($existing) {
            if ($existing->vote === $vote) {
                return response()->json(['message' => 'You have already voted on this report'], 409);
            }

            DB::table('report_verifications')
                ->where('id', $existing->id)
                ->update(['vote' => $vote, 'updated_at' => now()]);

            $report->increment('verification_score', $vote === 'up' ? 2 : -2);
        } else {
            DB::table('report_verifications')->insert([
                'user_id' => $request->user()->id,
                'community_report_id' => $report->id,
                'vote' => $vote,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $report->increment('verification_score', $vote === 'up' ? 1 : -1);
        }

        if ($report->fresh()->verification_score <= -3) {
            $report->update(['is_active' => false]);
        }

        return response()->json([
            'message' => $vote === 'up' ? 'Report verified' : 'Report disputed',
            'score' => $report->fresh()->verification_score,
        ]);
    }
}
