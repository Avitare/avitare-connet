<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Semanas de calendario (lunes-domingo) dentro de un mes, usadas para
 * numerar "semana 1..N" de un plan mensual. Un mes tiene 4 o 5 semanas
 * según cuántas semanas lunes-domingo tocan sus días.
 */
class CalendarWeeks
{
    public static function weeksInMonth(int $year, int $month): int
    {
        $firstWeekStart = Carbon::create($year, $month, 1)->startOfWeek(Carbon::MONDAY);
        $lastWeekStart = Carbon::create($year, $month, 1)->endOfMonth()->startOfWeek(Carbon::MONDAY);

        return $firstWeekStart->diffInWeeks($lastWeekStart) + 1;
    }

    public static function weekOfDate(Carbon $date, int $year, int $month): int
    {
        $firstWeekStart = Carbon::create($year, $month, 1)->startOfWeek(Carbon::MONDAY);
        $targetWeekStart = $date->copy()->startOfWeek(Carbon::MONDAY);

        $week = $firstWeekStart->diffInWeeks($targetWeekStart, false) + 1;

        return (int) max(1, min(self::weeksInMonth($year, $month), $week));
    }

    public static function weekCutoffDate(int $year, int $month, int $week): Carbon
    {
        $weekStart = Carbon::create($year, $month, 1)->startOfWeek(Carbon::MONDAY)->addWeeks($week - 1);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        return $weekEnd->lessThan($monthEnd) ? $weekEnd : $monthEnd;
    }
}
