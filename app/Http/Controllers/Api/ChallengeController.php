<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function index(Request $request)
    {
        $challenges = Challenge::where(function ($q) {
            $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        })
            ->withCount('participants')
            ->orderByDesc('starts_at')
            ->paginate(20);

        return response()->json($challenges);
    }

    public function show(Challenge $challenge)
    {
        $challenge->loadCount('participants');

        return response()->json(['challenge' => $challenge]);
    }

    public function join(Request $request, Challenge $challenge)
    {
        $participant = ChallengeParticipant::firstOrCreate(
            ['challenge_id' => $challenge->id, 'user_id' => $request->user()->id],
            ['progress' => 0]
        );

        return response()->json([
            'message' => 'Joined challenge',
            'participant' => $participant,
        ]);
    }

    public function myChallenges(Request $request)
    {
        $participations = ChallengeParticipant::with('challenge')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($participations);
    }
}
