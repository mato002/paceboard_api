<?php

namespace App\Policies;

use App\Models\SosAlert;
use App\Models\User;

class SosAlertPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function resolve(User $user, SosAlert $alert): bool
    {
        return $user->is_admin;
    }
}
