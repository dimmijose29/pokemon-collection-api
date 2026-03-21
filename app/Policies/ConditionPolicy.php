<?php

namespace App\Policies;

use App\Models\Condition;
use App\Models\User;

class ConditionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Condition $condition): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Condition $condition): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Condition $condition): bool
    {
        return $user->is_admin;
    }
}