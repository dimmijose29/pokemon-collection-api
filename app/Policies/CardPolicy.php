<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\User;

class CardPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Card $card): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Card $card): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Card $card): bool
    {
        return $user->is_admin;
    }
}
