<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedTrip;
use App\Models\Trip;
use App\Models\TripComment;
use App\Models\TripLike;
use App\Models\User;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function follow(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 422);
        }

        $request->user()->following()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'message' => 'Now following '.$user->name,
            'is_following' => true,
        ]);
    }

    public function unfollow(Request $request, User $user)
    {
        $request->user()->following()->detach($user->id);

        return response()->json([
            'message' => 'Unfollowed '.$user->name,
            'is_following' => false,
        ]);
    }

    public function followers(User $user)
    {
        return response()->json([
            'followers' => $user->followers()->select('users.id', 'users.name', 'users.country')->paginate(20),
        ]);
    }

    public function following(User $user)
    {
        return response()->json([
            'following' => $user->following()->select('users.id', 'users.name', 'users.country')->paginate(20),
        ]);
    }

    public function likeTrip(Request $request, Trip $trip)
    {
        $this->authorize('interact', $trip);

        TripLike::firstOrCreate([
            'user_id' => $request->user()->id,
            'trip_id' => $trip->id,
        ]);

        return response()->json([
            'message' => 'Trip liked',
            'likes_count' => $trip->likes()->count(),
        ]);
    }

    public function unlikeTrip(Request $request, Trip $trip)
    {
        $this->authorize('interact', $trip);

        TripLike::where('user_id', $request->user()->id)
            ->where('trip_id', $trip->id)
            ->delete();

        return response()->json([
            'message' => 'Trip unliked',
            'likes_count' => $trip->likes()->count(),
        ]);
    }

    public function commentTrip(Request $request, Trip $trip)
    {
        $this->authorize('interact', $trip);

        $request->validate(['body' => 'required|string|max:1000']);

        $comment = TripComment::create([
            'user_id' => $request->user()->id,
            'trip_id' => $trip->id,
            'body' => $request->body,
        ]);

        $comment->load('user:id,name');

        return response()->json(['message' => 'Comment added', 'comment' => $comment], 201);
    }

    public function tripComments(Trip $trip)
    {
        $this->authorize('view', $trip);

        $comments = $trip->comments()
            ->with('user:id,name')
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    public function saveTrip(Request $request, Trip $trip)
    {
        $this->authorize('view', $trip);

        SavedTrip::firstOrCreate([
            'user_id' => $request->user()->id,
            'trip_id' => $trip->id,
        ]);

        return response()->json(['message' => 'Trip saved', 'saved' => true]);
    }

    public function unsaveTrip(Request $request, Trip $trip)
    {
        SavedTrip::where('user_id', $request->user()->id)
            ->where('trip_id', $trip->id)
            ->delete();

        return response()->json(['message' => 'Trip removed from saved', 'saved' => false]);
    }

    public function savedTrips(Request $request)
    {
        $tripIds = SavedTrip::where('user_id', $request->user()->id)->pluck('trip_id');

        $trips = \App\Support\TripVisibility::visibleToQuery($request->user())
            ->whereIn('id', $tripIds)
            ->with(['user:id,name,avatar_path', 'route:id,name,start_city,end_city'])
            ->withCount(['likes', 'comments', 'photos'])
            ->latest('ended_at')
            ->paginate(15);

        return response()->json($trips);
    }
}
