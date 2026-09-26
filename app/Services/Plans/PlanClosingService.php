<?php

namespace App\Services\Plans;

use App\Enums\ActivityStatus;
use App\Enums\PlanStatus;
use App\Models\Activity;
use App\Models\MonthlyPlan;
use App\Models\PlanStatusLog;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class PlanClosingService
{
    public function __construct(
        private readonly ActivityMetricsService $metrics,
        private readonly ComplianceAggregationService $aggregation,
    ) {
    }

    /**
     * Cierra el plan vigente: queda de solo lectura y se calculan los cuatro
     * números finales (cumplimiento del plan aprobado, cumplimiento total del
     * mes, efectividad y puntualidad). Efectividad/puntualidad solo consideran
     * las actividades oficiales (congeladas en vigencia), igual que el
     * cumplimiento del plan aprobado.
     */
    public function close(MonthlyPlan $plan, User $closedBy, ?Carbon $reference = null, ?string $reason = null): MonthlyPlan
    {
        if ($plan->status !== PlanStatus::Vigente) {
            throw new DomainException('Solo un plan vigente puede cerrarse.');
        }

        $reference ??= Carbon::now();

        $officialActivities = Activity::query()
            ->whereHas('planGroup', fn ($query) => $query->where('monthly_plan_id', $plan->id))
            ->official()
            ->with(['latestProgressReport', 'progressReports'])
            ->get();

        $plan->final_compliance_plan_aprobado = $this->aggregation->forPlan($plan, $reference);
        $plan->final_compliance_total_mes = $this->aggregation->forPlanTotal($plan, $reference);
        $plan->final_effectiveness = $this->weightedRate(
            $officialActivities,
            fn (Activity $activity) => $this->metrics->status($activity, $reference) === ActivityStatus::Completada,
        );
        $plan->final_punctuality = $this->weightedRate(
            $officialActivities,
            fn (Activity $activity) => $this->finishedOnTime($activity),
        );
        $plan->status = PlanStatus::Cerrado;
        $plan->closed_by = $closedBy->id;
        $plan->closed_at = $reference;
        $plan->save();

        PlanStatusLog::create([
            'monthly_plan_id' => $plan->id,
            'from_status' => PlanStatus::Vigente->value,
            'to_status' => PlanStatus::Cerrado->value,
            'performed_by' => $closedBy->id,
            'reason' => $reason,
        ]);

        return $plan->fresh();
    }

    /**
     * Puntualidad: llegó a su meta y el primer reporte que la alcanzó cae
     * dentro de la semana planificada de cierre. Si nunca alcanzó la meta,
     * no fue puntual.
     */
    private function finishedOnTime(Activity $activity): bool
    {
        $target = $activity->target();

        if ($target <= 0) {
            return false;
        }

        $firstCompletion = $activity->progressReports->first(
            fn ($report) => (float) $report->value >= $target,
        );

        if (! $firstCompletion) {
            return false;
        }

        $period = $activity->planGroup->monthlyPlan->period;
        $completedWeek = $this->metrics->currentWeek($period, Carbon::parse($firstCompletion->created_at));

        return $completedWeek <= $activity->maxPlannedWeek();
    }

    /**
     * @param  Collection<int, Activity>  $activities
     */
    private function weightedRate(Collection $activities, callable $predicate): ?float
    {
        $totalWeight = 0.0;
        $weightedSum = 0.0;

        foreach ($activities as $activity) {
            $weight = (float) $activity->weight;
            $weightedSum += ($predicate($activity) ? 100.0 : 0.0) * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return null;
        }

        return round($weightedSum / $totalWeight, 2);
    }
}
