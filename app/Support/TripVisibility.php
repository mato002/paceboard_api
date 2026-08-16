<?php

namespace App\Support;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TripVisibility
{
    public static function visibleToQuery(?User $viewer): Builder
    {
        $query = Trip::query()->whereNotNull('ended_at');

        if (! $viewer) {
            return $query->where('visibility', 'public');
        }

        $followingIds = $viewer->following()->pluck('users.id')->push($viewer->id);

        return $query->where(function (Builder $q) use ($viewer, $followingIds) {
            $q->where('user_id', $viewer->id)
                ->orWhere('visibility', 'public')
                ->orWhere(function (Builder $inner) use ($followingIds) {
                    $inner->where('visibility', 'followers')
                        ->whereIn('user_id', $followingIds);
                });
        });
    }

    public static function canView(?User $viewer, Trip $trip): bool
    {
        if ($trip->ended_at === null) {
            return $viewer && $trip->user_id === $viewer->id;
        }

        if ($viewer && $trip->user_id === $viewer->id) {
            return true;
        }

        $visibility = $trip->visibility ?? 'public';

        return match ($visibility) {
            'public' => self::canViewProfile($viewer, $trip->user),
            'followers' => $viewer
                && ($viewer->following()->where('users.id', $trip->user_id)->exists() || $viewer->id === $trip->user_id),
            'private' => false,
            default => true,
        };
    }

    public static function canViewProfile(?User $viewer, User $profile): bool
    {
        if ($viewer && $viewer->id === $profile->id) {
            return true;
        }

        $visibility = $profile->profile_visibility ?? 'public';

        return match ($visibility) {
            'public' => true,
            'followers' => $viewer && $viewer->following()->where('users.id', $profile->id)->exists(),
            'private' => false,
            default => true,
        };
    }
}
