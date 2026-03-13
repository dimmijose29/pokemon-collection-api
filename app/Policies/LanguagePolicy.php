<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\User;

class LanguagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Language $language): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Language $language): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Language $language): bool
    {
        return $user->is_admin;
    }
}