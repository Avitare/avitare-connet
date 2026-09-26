<?php

namespace App\Services\Plans;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Period;
use App\Models\SystemSetting;
use App\Support\CalendarWeeks;
use Carbon\Carbon;

class ActivityMetricsService
{
    public function currentWeek(Period $period, ?Carbon $reference = null): int
    {
        $reference ??= Carbon::now();

        return CalendarWeeks::weekOfDate($reference, $period->year, $period->month);
    }

    public function expectedToDate(Activity $activity, ?Carbon $reference = null): float
    {
        $period = $activity->planGroup->monthlyPlan->period;

        return $this->expectedThroughWeek($activity, $this->currentWeek($period, $reference));
    }

    public function expectedThroughWeek(Activity $activity, int $week): float
    {
        $target = $activity->target();
        $planned = $activity->plannedWeekNumbers();

        if ($planned === []) {
            return 0.0;
        }

        $totalWeeks = count($planned);
        $elapsedWeeks = count(array_filter($planned, fn (int $w) => $w <= $week));

        if ($elapsedWeeks <= 0) {
            return 0.0;
        }

        if ($elapsedWeeks >= $totalWeeks) {
            return $target;
        }

        return round($target * $elapsedWeeks / $totalWeeks, 2);
    }

    /**
     * Fecha de corte de una semana del mes (semana de calendario
     * lunes-domingo, acotada al mes) — ver `CalendarWeeks::weekCutoffDate`.
     */
    public function weekCutoffDate(Period $period, int $week): Carbon
    {
        return CalendarWeeks::weekCutoffDate($period->year, $period->month, $week);
    }

    public function realValue(Activity $activity): float
    {
        return (float) ($activity->latestProgressReport?->value ?? 0);
    }

    /**
     * Valor "real" vigente al corte de una semana pasada: el último reporte
     * cuya fecha cae dentro o antes de esa semana (historial append-only,
     * no se recalcula nada, solo se lee la foto de ese momento).
     */
    public function realAsOfWeek(Activity $activity, Period $period, int $week): float
    {
        $cutoff = $this->weekCutoffDate($period, $week);

        // `progressReports()` ya ordena ASC por created_at; hay que resetear
        // ese orden antes de pedir el más reciente o queda ambiguo.
        return (float) ($activity->progressReports()
            ->reorder('created_at', 'desc')
            ->where('created_at', '<=', $cutoff)
            ->first()?->value ?? 0);
    }

    public function compliance(Activity $activity, ?Carbon $reference = null): float
    {
        $expected = $this->expectedToDate($activity, $reference);
        $real = $this->realValue($activity);

        if ($expected <= 0) {
            return $real > 0 ? 100.0 : 0.0;
        }

        return round(min(100.0, ($real / $expected) * 100), 2);
    }

    public function status(Activity $activity, ?Carbon $reference = null): ActivityStatus
    {
        $reference ??= Carbon::now();
        $target = $activity->target();
        $real = $this->realValue($activity);

        if ($target > 0 && $real >= $target) {
            return ActivityStatus::Completada;
        }

        $minPlannedWeek = $activity->minPlannedWeek();
        $maxPlannedWeek = $activity->maxPlannedWeek();

        if ($minPlannedWeek === null || $maxPlannedWeek === null) {
            return ActivityStatus::PorIniciar;
        }

        $period = $activity->planGroup->monthlyPlan->period;
        $currentWeek = $this->currentWeek($period, $reference);

        if ($currentWeek < $minPlannedWeek) {
            return ActivityStatus::PorIniciar;
        }

        if ($currentWeek > $maxPlannedWeek) {
            return ActivityStatus::Atrasada;
        }

        return $this->compliance($activity, $reference) >= SystemSetting::current()->activity_risk_threshold
            ? ActivityStatus::AlDia
            : ActivityStatus::EnRiesgo;
    }
}
