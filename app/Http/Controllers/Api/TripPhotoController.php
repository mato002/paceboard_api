<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripPhoto;
use App\Support\TripVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TripPhotoController extends Controller
{
    public function index(Request $request, Trip $trip)
    {
        if (! TripVisibility::canView($request->user(), $trip)) {
            abort(403);
        }

        $photos = $trip->photos()->with('user:id,name')->latest()->get()->map(fn ($p) => [
            ...$p->toArray(),
            'media_type' => $p->media_type ?? 'image',
            'url' => Storage::disk('public')->url($p->path),
        ]);

        return response()->json(['photos' => $photos]);
    }

    public function store(Request $request, Trip $trip)
    {
        $this->authorizeOwner($request, $trip);

        $request->validate([
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov,webm|max:20480',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov,webm|max:20480',
            'caption' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $file = $request->file('photo') ?? $request->file('media');
        if (! $file) {
            return response()->json(['message' => 'Photo or video file required'], 422);
        }

        $mediaType = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
        $path = $file->store("trips/{$trip->id}", 'public');

        $photo = TripPhoto::create([
            'trip_id' => $trip->id,
            'user_id' => $request->user()->id,
            'path' => $path,
            'media_type' => $mediaType,
            'caption' => $request->caption,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Photo uploaded',
            'photo' => [
                ...$photo->toArray(),
                'url' => Storage::disk('public')->url($path),
            ],
        ], 201);
    }

    public function destroy(Request $request, Trip $trip, TripPhoto $photo)
    {
        $this->authorizeOwner($request, $trip);

        if ($photo->trip_id !== $trip->id || $photo->user_id !== $request->user()->id) {
            abort(403);
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json(['message' => 'Photo deleted']);
    }

    private function authorizeOwner(Request $request, Trip $trip): void
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
