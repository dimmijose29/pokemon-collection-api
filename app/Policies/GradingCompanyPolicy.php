<?php

namespace App\Policies;

use App\Models\GradingCompany;
use App\Models\User;

class GradingCompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, GradingCompany $gradingCompany): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, GradingCompany $gradingCompany): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, GradingCompany $gradingCompany): bool
    {
        return $user->is_admin;
    }
}