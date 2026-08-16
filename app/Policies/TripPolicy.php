<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function view(?User $user, Trip $trip): bool
    {
        return \App\Support\TripVisibility::canView($user, $trip);
    }

    public function update(User $user, Trip $trip): bool
    {
        return $trip->user_id === $user->id;
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $trip->user_id === $user->id || $user->is_admin;
    }

    public function share(User $user, Trip $trip): bool
    {
        return $trip->user_id === $user->id;
    }

    public function interact(?User $user, Trip $trip): bool
    {
        return $trip->ended_at !== null;
    }
}
