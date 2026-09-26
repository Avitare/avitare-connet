<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Area $area): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Area $area): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Area $area): bool
    {
        return $user->hasRole('admin');
    }
}
