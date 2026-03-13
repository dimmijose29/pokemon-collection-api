<?php

namespace App\Policies;

use App\Models\CardType;
use App\Models\User;

class CardTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CardType $cardType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, CardType $cardType): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, CardType $cardType): bool
    {
        return $user->is_admin;
    }
}