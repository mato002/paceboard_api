<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TripPhoto;
use App\Models\User;
use App\Support\MediaUrl;
use App\Support\TripVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()
            ->loadCount(['trips', 'vehicles', 'followers', 'following'])
            ->load(['achievements']);

        return response()->json($this->formatProfile($user, includeEmail: true));
    }

    public function showPublic(Request $request, User $user)
    {
        if (! TripVisibility::canViewProfile($request->user(), $user)) {
            return response()->json(['message' => 'This profile is private'], 403);
        }

        $user->loadCount(['trips', 'followers', 'following'])
            ->load(['achievements']);

        $profile = $this->formatProfile($user, includeEmail: false);
        $profile['is_following'] = $request->user()->following()->where('users.id', $user->id)->exists();
        $profile['is_me'] = $request->user()->id === $user->id;

        return response()->json($profile);
    }

    public function driverTrips(Request $request, User $user)
    {
        if (! TripVisibility::canViewProfile($request->user(), $user)) {
            return response()->json(['message' => 'This profile is private'], 403);
        }

        $trips = TripVisibility::visibleToQuery($request->user())
            ->where('user_id', $user->id)
            ->with(['route:id,name,start_city,end_city', 'vehicle:id,nickname,manufacturer,model'])
            ->withCount(['likes', 'comments', 'photos'])
            ->latest('ended_at')
            ->paginate(15);

        return response()->json($trips);
    }

    public function driverPhotos(Request $request, User $user)
    {
        if (! TripVisibility::canViewProfile($request->user(), $user)) {
            return response()->json(['message' => 'This profile is private'], 403);
        }

        $visibleTripIds = TripVisibility::visibleToQuery($request->user())
            ->where('user_id', $user->id)
            ->select('id');

        $photos = TripPhoto::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($visibleTripIds) {
                $query->whereNull('trip_id')->orWhereIn('trip_id', $visibleTripIds);
            })
            ->latest()
            ->paginate(30)
            ->through(fn ($photo) => [
                'id' => $photo->id,
                'trip_id' => $photo->trip_id,
                'caption' => $photo->caption,
                'media_type' => $photo->media_type ?? 'image',
                'latitude' => $photo->latitude,
                'longitude' => $photo->longitude,
                'url' => MediaUrl::photo($photo),
                'created_at' => $photo->created_at,
            ]);

        return response()->json($photos);
    }

    public function storePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov,webm|max:20480',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov,webm|max:20480',
            'caption' => 'nullable|string|max:255',
            'trip_id' => 'nullable|integer|exists:trips,id',
        ]);

        $file = $request->file('photo') ?? $request->file('media');
        if (! $file) {
            return response()->json(['message' => 'Photo or video file required'], 422);
        }

        $user = $request->user();
        $trip = null;
        if ($request->filled('trip_id')) {
            $trip = $user->trips()->whereKey($request->integer('trip_id'))->first();
            if (! $trip) {
                return response()->json(['message' => 'Trip not found'], 404);
            }
        }

        $mediaType = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
        $folder = $trip ? "trips/{$trip->id}" : "gallery/{$user->id}";
        $path = $file->store($folder, 'public');

        $photo = TripPhoto::create([
            'trip_id' => $trip?->id,
            'user_id' => $user->id,
            'path' => $path,
            'media_type' => $mediaType,
            'caption' => $request->caption,
        ]);

        return response()->json([
            'message' => 'Photo uploaded',
            'photo' => [
                'id' => $photo->id,
                'trip_id' => $photo->trip_id,
                'caption' => $photo->caption,
                'media_type' => $photo->media_type,
                'url' => MediaUrl::photo($photo),
                'created_at' => $photo->created_at,
            ],
        ], 201);
    }

    public function destroyPhoto(Request $request, TripPhoto $photo)
    {
        if ($photo->user_id !== $request->user()->id) {
            abort(403);
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json(['message' => 'Photo deleted']);
    }

    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:100',
        ]);

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $radiusKm = (float) ($request->radius_km ?? 25);

        $activeTrips = \App\Models\Trip::query()
            ->whereNull('ended_at')
            ->with(['user:id,name,avatar_path', 'points' => fn ($q) => $q->latest('recorded_at')->limit(1)])
            ->get();

        $drivers = $activeTrips->map(function ($trip) use ($lat, $lng) {
            $point = $trip->points->first();
            if (! $point) {
                return null;
            }
            $distanceKm = $this->haversineKm($lat, $lng, (float) $point->latitude, (float) $point->longitude);

            return [
                'id' => $trip->user_id,
                'name' => $trip->user?->name,
                'avatar_url' => $trip->user?->avatar_url,
                'trip_id' => $trip->id,
                'latitude' => (float) $point->latitude,
                'longitude' => (float) $point->longitude,
                'distance_km' => round($distanceKm, 1),
            ];
        })
            ->filter()
            ->filter(fn ($d) => $d['distance_km'] <= $radiusKm)
            ->sortBy('distance_km')
            ->values();

        return response()->json($drivers);
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255|unique:users,phone,'.$request->user()->id,
            'country' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'profile_visibility' => 'nullable|string|in:public,followers,private',
        ]);

        $request->user()->update($validated);

        return response()->json($request->user());
    }

    public function avatar(User $user)
    {
        if (! $user->avatar_path || ! Storage::disk('public')->exists($user->avatar_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($user->avatar_path);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:5120']);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars/'.$user->id, 'public');
        $user->update(['avatar_path' => $path]);
        $user->refresh()
            ->loadCount(['trips', 'vehicles', 'followers', 'following'])
            ->load(['achievements']);

        return response()->json($this->formatProfile($user, includeEmail: true));
    }

    private function formatProfile(User $user, bool $includeEmail): array
    {
        $completedTrips = $user->trips()->whereNotNull('ended_at');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $includeEmail ? $user->email : null,
            'phone' => $includeEmail ? $user->phone : null,
            'email_verified_at' => $includeEmail ? $user->email_verified_at : null,
            'email_verified' => $user->hasVerifiedEmail(),
            'country' => $user->country,
            'county' => $user->county,
            'bio' => $user->bio,
            'profile_visibility' => $user->profile_visibility ?? 'public',
            'avatar_url' => $user->avatar_url,
            'phone_verified' => $user->phone_verified_at !== null,
            'driver_status' => $user->driver_status ?? 'active',
            'total_distance' => (float) $user->total_distance,
            'driving_hours' => (float) $user->driving_hours,
            'trips_count' => $user->trips_count ?? $completedTrips->count(),
            'vehicles_count' => $user->vehicles_count ?? 0,
            'followers_count' => $user->followers_count ?? 0,
            'following_count' => $user->following_count ?? 0,
            'highest_speed' => (float) ($completedTrips->max('top_speed') ?? 0),
            'average_speed' => round((float) ($completedTrips->avg('average_speed') ?? 0), 1),
            'achievements' => $user->achievements->map(fn ($a) => [
                'slug' => $a->slug,
                'name' => $a->name,
                'description' => $a->description,
                'icon' => $a->icon,
                'earned_at' => $a->pivot->earned_at,
            ]),
            'joined_at' => $user->created_at,
        ];
    }
}
