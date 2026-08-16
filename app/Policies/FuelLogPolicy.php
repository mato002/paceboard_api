<?php

namespace App\Policies;

use App\Models\FuelLog;
use App\Models\User;

class FuelLogPolicy
{
    public function delete(User $user, FuelLog $fuelLog): bool
    {
        return $fuelLog->user_id === $user->id;
    }
}
