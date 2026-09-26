<?php

namespace App\Services\Plans;

use App\Enums\ActivityStatus;
use App\Enums\PlanStatus;
use App\Models\Activity;
use App\Models\MonthlyPlan;
use App\Models\PlanGroup;
use App\Support\CalendarWeeks;
use Carbon\Carbon;
use DomainException;

class PlanCloningService
{
    public function __construct(private readonly ActivityMetricsService $metrics)
    {
    }

    /**
     * Puebla un plan en borrador (normalmente el que ya creó la apertura
     * automática del período) con la estructura del plan anterior: clona
     * todos los grupos y arrastra solo las actividades que no llegaron a
     * "completada", marcándolas `carried_over`. Las semanas se reinician a
     * todo el mes destino (todas sus semanas de calendario) porque las de la
     * actividad original ya no aplican; el jefe de área las reprograma al
     * armar el plan.
     */
    public function cloneInto(MonthlyPlan $target, MonthlyPlan $source, ?Carbon $reference = null): MonthlyPlan
    {
        if ($target->status !== PlanStatus::Borrador) {
            throw new DomainException('Solo se puede clonar sobre un plan en borrador.');
        }

        if ($target->groups()->exists()) {
            throw new DomainException('El plan destino ya tiene grupos; no se puede clonar sobre un plan con contenido.');
        }

        $reference ??= Carbon::now();

        $target->cloned_from_plan_id = $source->id;
        $target->save();

        foreach ($source->groups as $sourceGroup) {
            $targetGroup = PlanGroup::create([
                'monthly_plan_id' => $target->id,
                'name' => $sourceGroup->name,
                'position' => $sourceGroup->position,
            ]);

            foreach ($sourceGroup->activities as $activity) {
                if ($this->metrics->status($activity, $reference) === ActivityStatus::Completada) {
                    continue;
                }

                $carried = Activity::create([
                    'plan_group_id' => $targetGroup->id,
                    'responsible_name' => $activity->responsible_name,
                    'name' => $activity->name,
                    'progress_type' => $activity->progress_type,
                    'numeric_goal_target' => $activity->numeric_goal_target,
                    'weight' => $activity->weight,
                    'budget' => $activity->budget,
                    'deliverable' => $activity->deliverable,
                    'notes' => $activity->notes,
                    'carried_over' => true,
                    'carried_over_from_id' => $activity->id,
                ]);

                $targetWeeks = CalendarWeeks::weeksInMonth($target->period->year, $target->period->month);

                for ($week = 1; $week <= $targetWeeks; $week++) {
                    $carried->weeks()->create(['week_number' => $week]);
                }
            }
        }

        return $target->fresh();
    }
}
