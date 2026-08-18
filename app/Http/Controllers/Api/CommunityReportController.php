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
            'heading' => 'nullable|numeric|between:0,360',
            'road_name' => 'nullable|string|max:255',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $radiusKm = (float) $request->input('radius_km', 5);
        $limit = (int) $request->input('limit', 50);
        $heading = $request->filled('heading') ? (float) $request->heading : null;
        $roadName = $request->input('road_name');

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
            ->map(function (CommunityReport $report) use ($lat, $lng, $heading, $roadName) {
                $distanceKm = $this->haversineKm($lat, $lng, (float) $report->latitude, (float) $report->longitude);
                $bearing = $this->bearing($lat, $lng, (float) $report->latitude, (float) $report->longitude);
                $sameRoad = $this->roadsMatch($roadName, $report->road_name);
                $ahead = $heading === null ? true : $this->isAhead($heading, $bearing, $sameRoad);
                $payload = $report->toArray();
                $payload['distance_km'] = round($distanceKm, 3);
                $payload['distance_m'] = (int) round($distanceKm * 1000);
                $payload['bearing'] = round($bearing, 1);
                $payload['ahead'] = $ahead;
                $payload['same_road'] = $sameRoad;
                $payload['confidence'] = $report->confidence;
                $payload['stale'] = $report->isStale();

                return $payload;
            })
            ->filter(function (array $report) use ($radiusKm, $heading) {
                if ($report['distance_km'] > $radiusKm) {
                    return false;
                }
                if (! empty($report['stale'])) {
                    return false;
                }
                if ($heading !== null && empty($report['ahead'])) {
                    return $report['distance_km'] <= 0.08;
                }

                return true;
            })
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
            'type' => 'required|string|in:speed_camera,accident,pothole,traffic,police,hazard,road_closure,construction,fuel_price,parking,restaurant,breakdown,flooding,debris,school_zone',
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
            'verification_score' => 0,
            'confirmations_count' => 0,
            'dismissals_count' => 0,
            'status' => 'active',
            'is_active' => true,
            'last_confirmed_at' => now(),
            'expires_at' => CommunityReport::expiryForType($request->type),
        ]);

        app(\App\Services\ChallengeProgressService::class)->updateAfterReport($request->user());

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
            if ($vote === 'up') {
                $report->increment('confirmations_count');
                if ($existing->vote === 'down' && $report->dismissals_count > 0) {
                    $report->decrement('dismissals_count');
                }
            } else {
                $report->increment('dismissals_count');
                if ($existing->vote === 'up' && $report->confirmations_count > 0) {
                    $report->decrement('confirmations_count');
                }
            }
        } else {
            DB::table('report_verifications')->insert([
                'user_id' => $request->user()->id,
                'community_report_id' => $report->id,
                'vote' => $vote,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $report->increment('verification_score', $vote === 'up' ? 1 : -1);
            $report->increment($vote === 'up' ? 'confirmations_count' : 'dismissals_count');
        }

        $fresh = $report->fresh();
        if ($vote === 'up') {
            $fresh->update([
                'last_confirmed_at' => now(),
                'status' => 'active',
                'is_active' => true,
                'expires_at' => CommunityReport::expiryForType($fresh->type),
            ]);
        } elseif ($fresh->verification_score <= -3 || $fresh->dismissals_count >= 5) {
            $fresh->update(['is_active' => false, 'status' => 'archived']);
        } elseif ($fresh->isStale()) {
            $fresh->update(['status' => 'stale']);
        }

        return response()->json([
            'message' => $vote === 'up' ? 'Report verified' : 'Report disputed',
            'score' => $fresh->fresh()->verification_score,
            'confidence' => $fresh->fresh()->confidence,
            'confirmations_count' => $fresh->fresh()->confirmations_count,
        ]);
    }

    private function bearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLng = deg2rad($lng2 - $lng1);
        $lat1r = deg2rad($lat1);
        $lat2r = deg2rad($lat2);
        $y = sin($dLng) * cos($lat2r);
        $x = cos($lat1r) * sin($lat2r) - sin($lat1r) * cos($lat2r) * cos($dLng);
        $bearing = rad2deg(atan2($y, $x));

        return fmod($bearing + 360, 360);
    }

    private function isAhead(float $heading, float $bearing, bool $sameRoad): bool
    {
        $cone = $sameRoad ? 80 : 50;
        $diff = abs(fmod($bearing - $heading + 540, 360) - 180);

        return $diff <= $cone;
    }

    private function roadsMatch(?string $a, ?string $b): bool
    {
        if (! $a || ! $b) {
            return false;
        }

        $normalize = static function (string $value): string {
            $value = strtolower($value);
            $value = preg_replace('/\b(road|rd|highway|hwy|avenue|ave|street|st|boulevard|blvd|drive|dr)\b/', '', $value) ?? $value;
            $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

            return trim($value);
        };

        $na = $normalize($a);
        $nb = $normalize($b);
        if ($na === '' || $nb === '') {
            return false;
        }
        if ($na === $nb) {
            return true;
        }

        return str_contains($na, $nb) || str_contains($nb, $na);
    }
}
