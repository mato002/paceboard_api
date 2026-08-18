<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use App\Support\ChallengePresenter;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $category = $request->string('category')->toString();

        $query = Challenge::query()
            ->withCount('participants')
            ->orderByDesc('starts_at');

        if ($category !== '' && $category !== 'all') {
            $types = $this->typesForCategory($category);
            if ($types !== []) {
                $query->whereIn('type', $types);
            }
        }

        $page = $query->paginate(30);
        $mine = ChallengeParticipant::query()
            ->where('user_id', $user->id)
            ->whereIn('challenge_id', $page->getCollection()->pluck('id'))
            ->get()
            ->keyBy('challenge_id');

        $page->setCollection(
            $page->getCollection()->map(
                fn (Challenge $challenge) => ChallengePresenter::format($challenge, $user, $mine->get($challenge->id))
            )
        );

        return response()->json($page);
    }

    public function summary(Request $request)
    {
        return response()->json(ChallengePresenter::summary($request->user()));
    }

    public function show(Request $request, Challenge $challenge)
    {
        $challenge->loadCount('participants');
        $participant = ChallengeParticipant::query()
            ->where('challenge_id', $challenge->id)
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'challenge' => ChallengePresenter::format($challenge, $request->user(), $participant),
        ]);
    }

    public function join(Request $request, Challenge $challenge)
    {
        if ($challenge->ends_at && $challenge->ends_at->isPast()) {
            return response()->json(['message' => 'This challenge has ended'], 422);
        }

        $participant = ChallengeParticipant::firstOrCreate(
            ['challenge_id' => $challenge->id, 'user_id' => $request->user()->id],
            ['progress' => 0]
        );

        $challenge->loadCount('participants');

        return response()->json([
            'message' => 'Joined challenge',
            'challenge' => ChallengePresenter::format($challenge, $request->user(), $participant),
            'participant' => $participant,
        ]);
    }

    public function myChallenges(Request $request)
    {
        $participations = ChallengeParticipant::with(['challenge' => fn ($q) => $q->withCount('participants')])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        $participations->setCollection(
            $participations->getCollection()
                ->filter(fn ($row) => $row->challenge !== null)
                ->map(fn ($row) => ChallengePresenter::format($row->challenge, $request->user(), $row))
                ->values()
        );

        return response()->json($participations);
    }

    private function typesForCategory(string $category): array
    {
        return match ($category) {
            'routes' => ['route'],
            'distance' => ['distance', 'trips'],
            'safety' => ['safety'],
            'community' => ['community'],
            'competitive' => ['weekend', 'night_drive', 'score', 'rank'],
            default => [],
        };
    }
}
