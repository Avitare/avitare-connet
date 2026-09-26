<?php

namespace App\Services\Plans;

use App\Models\Activity;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\PlanGroup;
use App\Support\CalendarWeeks;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ComplianceAggregationService
{
    public function __construct(private readonly ActivityMetricsService $metrics)
    {
    }

    public function forGroup(PlanGroup $group, ?Carbon $reference = null): ?float
    {
        $activities = $group->activities()
            ->official()
            ->with('latestProgressReport')
            ->get();

        return $this->weightedAverage($activities, $reference ?? Carbon::now());
    }

    /**
     * Cumplimiento del grupo incluyendo actividades agregadas después de
     * aprobar (ver forPlanTotal) — fallback cuando forGroup() da null.
     */
    public function forGroupTotal(PlanGroup $group, ?Carbon $reference = null): ?float
    {
        $activities = $group->activities()
            ->with('latestProgressReport')
            ->get();

        return $this->weightedAverage($activities, $reference ?? Carbon::now());
    }

    public function forPlan(MonthlyPlan $plan, ?Carbon $reference = null): ?float
    {
        $activities = Activity::query()
            ->whereHas('planGroup', fn ($query) => $query->where('monthly_plan_id', $plan->id))
            ->official()
            ->with('latestProgressReport')
            ->get();

        return $this->weightedAverage($activities, $reference ?? Carbon::now());
    }

    /**
     * Cumplimiento del mes completo del plan, incluyendo las actividades
     * agregadas después de la aprobación ("total del mes" vs. "plan aprobado").
     */
    public function forPlanTotal(MonthlyPlan $plan, ?Carbon $reference = null): ?float
    {
        $activities = Activity::query()
            ->whereHas('planGroup', fn ($query) => $query->where('monthly_plan_id', $plan->id))
            ->with('latestProgressReport')
            ->get();

        return $this->weightedAverage($activities, $reference ?? Carbon::now());
    }

    public function forCompany(Company $company, Period $period, ?Carbon $reference = null): ?float
    {
        $planIds = MonthlyPlan::query()
            ->where('period_id', $period->id)
            ->whereHas('area', fn ($query) => $query->where('company_id', $company->id))
            ->pluck('id');

        $activities = Activity::query()
            ->whereHas('planGroup', fn ($query) => $query->whereIn('monthly_plan_id', $planIds))
            ->official()
            ->with('latestProgressReport')
            ->get();

        return $this->weightedAverage($activities, $reference ?? Carbon::now());
    }

    /**
     * Cumplimiento ponderado del plan en cada semana del mes (1-4), usando
     * el "real" histórico vigente a esa fecha en vez del último reporte
     * global — permite comparar el ritmo semanal de distintas áreas dentro
     * del mismo período.
     *
     * @return array<int, float|null>
     */
    public function planWeeklyCompliance(MonthlyPlan $plan, Period $period): array
    {
        $activities = Activity::query()
            ->whereHas('planGroup', fn ($query) => $query->where('monthly_plan_id', $plan->id))
            ->official()
            ->get();

        $result = [];

        for ($week = 1; $week <= CalendarWeeks::weeksInMonth($period->year, $period->month); $week++) {
            $result[$week] = $this->weightedAverageAtWeek($activities, $period, $week);
        }

        return $result;
    }

    /**
     * @param  Collection<int, Activity>  $activities
     */
    private function weightedAverage(Collection $activities, Carbon $reference): ?float
    {
        $totalWeight = 0.0;
        $weightedSum = 0.0;

        foreach ($activities as $activity) {
            $weight = (float) $activity->weight;
            $weightedSum += $this->metrics->compliance($activity, $reference) * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return null;
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * @param  Collection<int, Activity>  $activities
     */
    private function weightedAverageAtWeek(Collection $activities, Period $period, int $week): ?float
    {
        $totalWeight = 0.0;
        $weightedSum = 0.0;

        foreach ($activities as $activity) {
            $weight = (float) $activity->weight;
            $expected = $this->metrics->expectedThroughWeek($activity, $week);
            $real = $this->metrics->realAsOfWeek($activity, $period, $week);

            $compliance = $expected <= 0
                ? ($real > 0 ? 100.0 : 0.0)
                : min(100.0, ($real / $expected) * 100);

            $weightedSum += $compliance * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            return null;
        }

        return round($weightedSum / $totalWeight, 2);
    }
}
