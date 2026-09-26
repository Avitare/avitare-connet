<?php

namespace App\Services\Plans;

use App\Enums\PlanStatus;
use App\Models\MonthlyPlan;
use App\Models\PlanStatusLog;
use App\Models\User;
use Carbon\Carbon;
use DomainException;

class PlanApprovalService
{
    /**
     * Aprueba el plan: queda vigente y sus actividades/semanas se congelan.
     * A partir de aquí, cualquier actividad nueva se marca `added_after_approval`
     * automáticamente (ver Activity::booted()).
     */
    public function approve(MonthlyPlan $plan, User $approvedBy, ?Carbon $reference = null, ?string $reason = null): MonthlyPlan
    {
        if ($plan->status !== PlanStatus::Borrador) {
            throw new DomainException('Solo un plan en borrador puede aprobarse.');
        }

        $reference ??= Carbon::now();

        $plan->status = PlanStatus::Vigente;
        $plan->approved_by = $approvedBy->id;
        $plan->approved_at = $reference;
        $plan->frozen_at = $reference;
        $plan->save();

        PlanStatusLog::create([
            'monthly_plan_id' => $plan->id,
            'from_status' => PlanStatus::Borrador->value,
            'to_status' => PlanStatus::Vigente->value,
            'performed_by' => $approvedBy->id,
            'reason' => $reason,
        ]);

        return $plan->fresh();
    }
}
