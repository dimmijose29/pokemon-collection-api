<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, User $model): bool
    {
        return $user->is_admin || $user->id === $model->id;
    }

    public function update(User $user, User $model): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, User $model): bool
    {
        // Solo admin, e non può eliminare se stesso
        return $user->is_admin && $user->id !== $model->id;
    }

    public function restore(User $user): bool
    {
        return $user->is_admin;
    }
}
