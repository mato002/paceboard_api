<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\ChallengePresenter;

class ChallengeProgressService
{
    public function updateAfterTrip(Trip $trip): void
    {
        $user = $trip->user;
        $this->advanceJoined($user, function (Challenge $challenge) use ($trip) {
            return match ($challenge->type) {
                'distance' => (int) round((float) $trip->distance),
                'trips' => 1,
                'night_drive' => (
                    $trip->started_at && (
                        (int) $trip->started_at->format('H') >= 22 || (int) $trip->started_at->format('H') < 5
                    )
                ) ? 1 : 0,
                'weekend' => ($trip->started_at && $trip->started_at->isWeekend()) ? 1 : 0,
                'route' => ($trip->route_id && $this->matchesRouteChallenge($challenge, $trip)) ? 1 : 0,
                'safety' => ((float) $trip->score >= 80) ? 1 : 0,
                default => 0,
            };
        });
    }

    public function updateAfterReport(User $user): void
    {
        $this->advanceJoined($user, function (Challenge $challenge) {
            return $challenge->type === 'community' ? 1 : 0;
        });
    }

    private function advanceJoined(User $user, callable $incrementFor): void
    {
        $activeIds = Challenge::query()
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->pluck('id');

        $participants = ChallengeParticipant::query()
            ->with('challenge')
            ->where('user_id', $user->id)
            ->whereIn('challenge_id', $activeIds)
            ->get();

        foreach ($participants as $participant) {
            $challenge = $participant->challenge;
            if (! $challenge || $participant->completed) {
                continue;
            }

            $increment = (int) $incrementFor($challenge);
            if ($increment <= 0) {
                continue;
            }

            $participant->increment('progress', $increment);
            $fresh = $participant->fresh();
            if ($fresh && $fresh->progress >= $challenge->target_value) {
                $this->complete($user, $challenge, $fresh);
            }
        }
    }

    private function complete(User $user, Challenge $challenge, ChallengeParticipant $participant): void
    {
        $participant->update(['completed' => true, 'completed_at' => now()]);

        if ($challenge->reward_points > 0) {
            $user->increment('reward_points', $challenge->reward_points);
        }

        $badge = ChallengePresenter::badgeForType($challenge->type);
        if ($badge && ! empty($badge['slug'])) {
            app(AchievementService::class)->award($user, $badge['slug']);
        }

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'challenge_won',
            'title' => 'Challenge completed',
            'body' => "You completed {$challenge->title}".($challenge->reward_points > 0 ? " (+{$challenge->reward_points} pts)" : ''),
            'data' => [
                'challenge_id' => $challenge->id,
                'reward_points' => $challenge->reward_points,
                'badge' => $badge['slug'] ?? null,
            ],
        ]);
    }

    private function matchesRouteChallenge(Challenge $challenge, Trip $trip): bool
    {
        if (! $trip->route) {
            return false;
        }

        $title = strtolower($challenge->title);

        return str_contains($title, strtolower((string) $trip->route->start_city))
            && str_contains($title, strtolower((string) $trip->route->end_city));
    }
}
