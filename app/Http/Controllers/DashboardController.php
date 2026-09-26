<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Area;
use App\Models\MonthlyPlan;
use App\Models\PlanGroup;
use App\Models\Period;
use App\Models\User;
use App\Services\Plans\ActivityMetricsService;
use App\Services\Plans\ComplianceAggregationService;
use App\Support\CalendarWeeks;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, ActivityMetricsService $metrics, ComplianceAggregationService $aggregation): Response
    {
        $user = $request->user();
        $periodId = $request->integer('period_id') ?: null;
        $period = $periodId
            ? Period::find($periodId)
            : Period::orderByDesc('year')->orderByDesc('month')->first();
        $reference = Carbon::now();

        $data = [
            'period' => $period?->only(['id', 'year', 'month']),
        ];

        if ($user->hasRole('gerencia') || $user->hasRole('admin')) {
            $data['areasOverview'] = $period ? $this->areasOverview($period, $reference, $aggregation) : [];
            $data['periods'] = Period::orderByDesc('year')->orderByDesc('month')->get(['id', 'year', 'month']);
        }

        if ($user->hasRole('jefe_area') && $user->area_id) {
            $data['myPlan'] = $period ? $this->myAreaPlan($user, $period, $reference, $metrics, $aggregation) : null;
            $data['periods'] = Period::orderByDesc('year')->orderByDesc('month')->get(['id', 'year', 'month']);
        }

        return Inertia::render('Dashboard', $data);
    }

    private function areasOverview(Period $period, Carbon $reference, ComplianceAggregationService $aggregation): array
    {
        return Area::query()
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Area $area) use ($period, $reference, $aggregation) {
                $plan = MonthlyPlan::where('area_id', $area->id)->where('period_id', $period->id)->first();

                $compliance = $plan
                    ? ($aggregation->forPlan($plan, $reference) ?? $aggregation->forPlanTotal($plan, $reference))
                    : null;

                return [
                    'area' => $area->only(['id', 'name']),
                    'plan_id' => $plan?->id,
                    'status' => $plan?->status?->value,
                    'compliance' => $compliance,
                    'weekly' => $plan ? $aggregation->planWeeklyCompliance($plan, $period) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function myAreaPlan(User $user, Period $period, Carbon $reference, ActivityMetricsService $metrics, ComplianceAggregationService $aggregation): ?array
    {
        $plan = MonthlyPlan::where('area_id', $user->area_id)->where('period_id', $period->id)->first();

        if (! $plan) {
            return null;
        }

        $plan->load('groups.activities.weeks');

        $activities = $plan->groups->flatMap(fn (PlanGroup $group) => $group->activities);

        $attention = $activities
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
                'responsible_name' => $activity->responsible_name,
                'status' => $metrics->status($activity, $reference)->value,
            ])
            ->filter(fn (array $activity) => in_array($activity['status'], ['en_riesgo', 'atrasada'], true))
            ->values()
            ->all();

        // Solo las actividades "oficiales" (congeladas al aprobar) cuentan para
        // el cumplimiento formal; si el plan se llenó todo después de aprobar
        // (o sigue en borrador), forPlan() no tiene de qué promediar y da
        // null — usamos el total del mes para no mostrarle un guion vacío.
        $officialCompliance = $aggregation->forPlan($plan, $reference);

        $totalWeeks = CalendarWeeks::weeksInMonth($period->year, $period->month);
        $isCurrentPeriod = $period->year === $reference->year && $period->month === $reference->month;
        $currentWeek = $isCurrentPeriod ? $metrics->currentWeek($period, $reference) : null;

        // "Por vencer": no completadas cuya última semana planificada es la
        // semana actual o la siguiente (1 semana de margen). Sin semana
        // actual (plan de un mes que no es el calendario vigente) no hay
        // "hoy" contra el cual medir, así que queda vacío.
        $dueSoon = $currentWeek === null
            ? []
            : $activities
                ->filter(function (Activity $activity) use ($metrics, $reference, $currentWeek) {
                    $dueWeek = $activity->maxPlannedWeek();

                    if ($dueWeek === null || $metrics->status($activity, $reference) === ActivityStatus::Completada) {
                        return false;
                    }

                    return $dueWeek === $currentWeek || $dueWeek === $currentWeek + 1;
                })
                ->map(fn (Activity $activity) => [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'responsible_name' => $activity->responsible_name,
                    'due_week' => $activity->maxPlannedWeek(),
                ])
                ->sortBy('due_week')
                ->values()
                ->all();

        $groups = $plan->groups
            ->filter(fn (PlanGroup $group) => $group->activities->isNotEmpty())
            ->map(fn (PlanGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'compliance' => $aggregation->forGroup($group, $reference) ?? $aggregation->forGroupTotal($group, $reference),
                'activities' => $group->activities->map(fn (Activity $activity) => [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'weeks' => $activity->weeks->map(fn ($w) => ['week_number' => $w->week_number]),
                    'compliance' => $metrics->compliance($activity, $reference),
                    'status' => $metrics->status($activity, $reference)->value,
                    'closed' => $activity->isClosed(),
                    'can_close' => $user->can('close', $activity),
                ]),
            ])
            ->values();

        return [
            'plan_id' => $plan->id,
            'status' => $plan->status->value,
            'compliance' => $officialCompliance ?? $aggregation->forPlanTotal($plan, $reference),
            'compliance_is_total' => $officialCompliance === null,
            'activities_total' => $activities->count(),
            'activities_completed' => $activities->filter(
                fn (Activity $activity) => $metrics->status($activity, $reference) === ActivityStatus::Completada,
            )->count(),
            'total_weeks' => $totalWeeks,
            'current_week' => $currentWeek,
            'groups' => $groups,
            'attention' => $attention,
            'due_soon' => $dueSoon,
        ];
    }
}
