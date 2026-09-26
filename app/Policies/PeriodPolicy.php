<?php

namespace App\Policies;

use App\Models\Period;
use App\Models\User;

class PeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'gerencia']);
    }

    public function view(User $user, Period $period): bool
    {
        return $user->hasAnyRole(['admin', 'gerencia']);
    }

    public function manage(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
