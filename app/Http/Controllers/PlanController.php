<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\PlanGroup;
use App\Services\Plans\ActivityMetricsService;
use App\Services\Plans\ComplianceAggregationService;
use App\Services\Plans\PlanApprovalService;
use App\Services\Plans\PlanClosingService;
use App\Services\Plans\PlanCloningService;
use App\Services\Plans\PeriodOpeningService;
use App\Support\CalendarWeeks;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $periodId = $request->integer('period_id') ?: null;

        $plans = MonthlyPlan::query()
            ->with(['area', 'period'])
            ->when(! $user->hasRole('gerencia'), fn ($query) => $query->where('area_id', $user->area_id))
            ->when($periodId, fn ($query) => $query->where('period_id', $periodId))
            ->join('periods', 'periods.id', '=', 'monthly_plans.period_id')
            ->orderByDesc('periods.year')
            ->orderByDesc('periods.month')
            ->select('monthly_plans.*')
            ->get();

        return Inertia::render('Plans/Index', [
            'plans' => $plans,
            'periods' => Period::orderByDesc('year')->orderByDesc('month')->get(['id', 'year', 'month']),
            'selectedPeriodId' => $periodId,
            'canCreatePlan' => $user->hasRole('jefe_area') && $user->area_id !== null,
        ]);
    }

    public function show(MonthlyPlan $plan, ActivityMetricsService $metrics, ComplianceAggregationService $aggregation): Response
    {
        $this->authorize('view', $plan);

        $plan->load(['area', 'period', 'groups.activities.weeks', 'groups.activities.latestProgressReport']);

        $reference = Carbon::now();
        $user = request()->user();
        $totalWeeks = CalendarWeeks::weeksInMonth($plan->period->year, $plan->period->month);
        $isCurrentPeriod = $plan->period->year === $reference->year && $plan->period->month === $reference->month;
        $currentWeek = $isCurrentPeriod ? $metrics->currentWeek($plan->period, $reference) : null;

        $groups = $plan->groups->map(fn (PlanGroup $group) => [
            'id' => $group->id,
            'name' => $group->name,
            'compliance' => $aggregation->forGroup($group, $reference) ?? $aggregation->forGroupTotal($group, $reference),
            'activities' => $group->activities->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
                'responsible_name' => $activity->responsible_name,
                'weeks' => $activity->weeks->map(fn ($w) => [
                    'id' => $w->id,
                    'week_number' => $w->week_number,
                    'completed_at' => $w->completed_at,
                ]),
                'progress_type' => $activity->progress_type->value,
                'numeric_goal_target' => $activity->numeric_goal_target,
                'weight' => (float) $activity->weight,
                'budget' => $activity->budget,
                'deliverable' => $activity->deliverable_type ? [
                    'type' => $activity->deliverable_type,
                    'caption' => $activity->deliverable,
                    'url' => match ($activity->deliverable_type) {
                        'link' => $activity->deliverable_url,
                        'file' => route('activities.deliverable.download', $activity->id),
                        default => null,
                    },
                    'file_name' => $activity->deliverable_original_name,
                    'mime_type' => $activity->deliverable_mime_type,
                ] : null,
                'notes' => $activity->notes,
                'carried_over' => $activity->carried_over,
                'added_after_approval' => $activity->added_after_approval,
                'real' => $metrics->realValue($activity),
                'expected' => $metrics->expectedToDate($activity, $reference),
                'compliance' => $metrics->compliance($activity, $reference),
                'status' => $metrics->status($activity, $reference)->value,
                'completed' => $activity->target() > 0 && $metrics->realValue($activity) >= $activity->target(),
                'closed' => $activity->isClosed(),
                'closed_at' => $activity->closed_at,
                'can_report' => $user->can('report', $activity),
                'can_close' => $user->can('close', $activity),
            ]),
        ]);

        $previousPlan = $this->previousPlan($plan);

        return Inertia::render('Plans/Show', [
            'plan' => [
                'id' => $plan->id,
                'status' => $plan->status->value,
                'area' => $plan->area->only(['id', 'name']),
                'period' => $plan->period->only(['id', 'year', 'month']),
                'approved_at' => $plan->approved_at,
                'closed_at' => $plan->closed_at,
                'final_compliance_plan_aprobado' => $plan->final_compliance_plan_aprobado,
                'final_compliance_total_mes' => $plan->final_compliance_total_mes,
                'final_effectiveness' => $plan->final_effectiveness,
                'final_punctuality' => $plan->final_punctuality,
                'total_weeks' => $totalWeeks,
                'current_week' => $currentWeek,
            ],
            'compliance' => $aggregation->forPlan($plan, $reference),
            'groups' => $groups,
            'canManage' => $user->can('manage', $plan),
            'canClone' => $user->can('manage', $plan) && $plan->status->value === 'borrador' && $plan->groups->isEmpty() && $previousPlan !== null,
        ]);
    }

    /**
     * El jefe de área crea (si todavía no existe) el plan en borrador de su
     * propia área para el período elegido. Acepta un `period_id` existente,
     * o un `year`+`month` de cualquier mes (lo abre si todavía no existe
     * como período — mismo mecanismo que la apertura automática de fin de
     * mes, pero disparado a mano). Sin ninguno de los dos, cae al período
     * más reciente o abre el siguiente si no hay ninguno todavía. Un solo
     * plan por área y período: `firstOrCreate` + el índice único en
     * `monthly_plans` hacen que elegir un período que ya tiene plan
     * simplemente te lleve al que ya existe, no lo duplica.
     */
    public function createMine(Request $request, PeriodOpeningService $service): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->hasRole('jefe_area') && $user->area_id, 403);

        $periodId = $request->integer('period_id') ?: null;

        if ($periodId) {
            $period = Period::findOrFail($periodId);
        } elseif ($request->filled('year') && $request->filled('month')) {
            $data = $request->validate([
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'between:1,12'],
            ]);

            $period = Period::firstOrCreate(['year' => $data['year'], 'month' => $data['month']]);
        } else {
            $period = Period::orderByDesc('year')->orderByDesc('month')->first() ?? $service->openNext();
        }

        $plan = MonthlyPlan::firstOrCreate([
            'period_id' => $period->id,
            'area_id' => $user->area_id,
        ]);

        return to_route('plans.show', $plan);
    }

    public function approve(MonthlyPlan $plan, PlanApprovalService $service): RedirectResponse
    {
        $this->authorize('manage', $plan);

        try {
            $service->approve($plan, request()->user());
        } catch (DomainException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back();
    }

    public function close(MonthlyPlan $plan, PlanClosingService $service): RedirectResponse
    {
        $this->authorize('manage', $plan);

        try {
            $service->close($plan, request()->user());
        } catch (DomainException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back();
    }

    public function clone(MonthlyPlan $plan, PlanCloningService $service): RedirectResponse
    {
        $this->authorize('manage', $plan);

        $previous = $this->previousPlan($plan);

        if (! $previous) {
            return back()->withErrors(['plan' => 'No hay un plan anterior para clonar.']);
        }

        try {
            $service->cloneInto($plan, $previous);
        } catch (DomainException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        return back();
    }

    private function previousPlan(MonthlyPlan $plan): ?MonthlyPlan
    {
        $previousMonth = Carbon::create($plan->period->year, $plan->period->month, 1)->subMonthNoOverflow();

        $previousPeriod = Period::where('year', $previousMonth->year)->where('month', $previousMonth->month)->first();

        if (! $previousPeriod) {
            return null;
        }

        return MonthlyPlan::where('period_id', $previousPeriod->id)->where('area_id', $plan->area_id)->first();
    }
}
