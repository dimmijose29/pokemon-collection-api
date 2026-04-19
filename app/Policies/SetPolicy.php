<?php

namespace App\Policies;

use App\Models\Set;
use App\Models\User;

class SetPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Set $set): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Set $set): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Set $set): bool
    {
        return $user->is_admin;
    }
}
