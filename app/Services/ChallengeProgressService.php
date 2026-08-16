<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use App\Models\Trip;
use App\Models\UserNotification;

class ChallengeProgressService
{
    public function updateAfterTrip(Trip $trip): void
    {
        $user = $trip->user;
        $activeChallenges = Challenge::where(function ($q) {
            $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        })->get();

        foreach ($activeChallenges as $challenge) {
            $participant = ChallengeParticipant::firstOrCreate(
                ['challenge_id' => $challenge->id, 'user_id' => $user->id],
                ['progress' => 0]
            );

            if ($participant->completed) {
                continue;
            }

            $increment = match ($challenge->type) {
                'distance' => (int) round((float) $trip->distance),
                'trips' => 1,
                'night_drive' => (
                    $trip->started_at && (
                        (int) $trip->started_at->format('H') >= 22 || (int) $trip->started_at->format('H') < 5
                    )
                ) ? 1 : 0,
                'weekend' => ($trip->started_at && $trip->started_at->isWeekend()) ? 1 : 0,
                'route' => ($trip->route_id && $this->matchesRouteChallenge($challenge, $trip)) ? 1 : 0,
                default => 0,
            };

            if ($increment <= 0) {
                continue;
            }

            $participant->increment('progress', $increment);

            if ($participant->fresh()->progress >= $challenge->target_value) {
                $participant->update(['completed' => true, 'completed_at' => now()]);

                if ($challenge->reward_points > 0) {
                    $user->increment('reward_points', $challenge->reward_points);
                }

                UserNotification::create([
                    'user_id' => $user->id,
                    'type' => 'challenge_won',
                    'title' => 'Challenge Completed!',
                    'body' => "You completed: {$challenge->title}".($challenge->reward_points > 0 ? " (+{$challenge->reward_points} pts)" : ''),
                    'data' => ['challenge_id' => $challenge->id, 'reward_points' => $challenge->reward_points],
                ]);
            }
        }
    }

    private function matchesRouteChallenge(Challenge $challenge, Trip $trip): bool
    {
        if (! $trip->route) {
            return false;
        }

        $title = strtolower($challenge->title);

        return str_contains($title, strtolower($trip->route->start_city))
            && str_contains($title, strtolower($trip->route->end_city));
    }
}
