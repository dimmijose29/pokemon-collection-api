<?php

namespace App\Policies;

use App\Models\Rarity;
use App\Models\User;

class RarityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rarity $rarity): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Rarity $rarity): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Rarity $rarity): bool
    {
        return $user->is_admin;
    }
}