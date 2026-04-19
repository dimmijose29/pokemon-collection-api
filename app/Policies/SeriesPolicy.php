<?php

namespace App\Policies;

use App\Models\Series;
use App\Models\User;

class SeriesPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Series $series): bool { return true; }
    public function create(User $user): bool { return $user->is_admin; }
    public function update(User $user, Series $series): bool { return $user->is_admin; }
    public function delete(User $user, Series $series): bool { return $user->is_admin; }
    public function restore(User $user, Series $series): bool { return $user->is_admin; }
}
