<?php

namespace App\Policies;

use App\Enums\PlanStatus;
use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Reportar avance: el jefe del área del plan (el responsable de una
     * actividad es solo un nombre, no una cuenta que inicia sesión).
     * Nada se reporta sobre un plan ya cerrado, ni sobre una actividad
     * cerrada individualmente aunque el resto del plan siga abierto.
     */
    public function report(User $user, Activity $activity): bool
    {
        $plan = $activity->planGroup->monthlyPlan;

        if ($plan->status === PlanStatus::Cerrado || $activity->isClosed()) {
            return false;
        }

        return $user->hasRole('jefe_area') && $user->area_id === $plan->area_id;
    }

    /**
     * Cerrar/reabrir una actividad individual: misma persona que gestiona
     * el plan (jefe de área), independiente del estado de la actividad —
     * a diferencia de report(), sí se permite sobre una actividad ya
     * cerrada (para poder reabrirla).
     */
    public function close(User $user, Activity $activity): bool
    {
        $plan = $activity->planGroup->monthlyPlan;

        if ($plan->status === PlanStatus::Cerrado) {
            return false;
        }

        return $user->hasRole('jefe_area') && $user->area_id === $plan->area_id;
    }
}
