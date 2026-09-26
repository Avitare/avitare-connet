<?php

namespace App\Policies;

use App\Enums\PlanStatus;
use App\Models\MonthlyPlan;
use App\Models\User;

class MonthlyPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MonthlyPlan $plan): bool
    {
        return $user->hasRole('gerencia') || $user->area_id === $plan->area_id;
    }

    /**
     * Armar/aprobar/cerrar/clonar el plan y su contenido (grupos, actividades).
     */
    public function manage(User $user, MonthlyPlan $plan): bool
    {
        if ($plan->status === PlanStatus::Cerrado) {
            return false;
        }

        return $user->hasRole('jefe_area') && $user->area_id === $plan->area_id;
    }
}
