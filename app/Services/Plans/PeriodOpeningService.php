<?php

namespace App\Services\Plans;

use App\Models\Area;
use App\Models\MonthlyPlan;
use App\Models\Period;
use Carbon\Carbon;

class PeriodOpeningService
{
    /**
     * Crea (si no existe) el período siguiente al actual y un plan en borrador
     * para cada área activa. Idempotente: puede ejecutarse varias veces sin
     * duplicar períodos ni planes.
     */
    public function openNext(?Carbon $reference = null): Period
    {
        $reference ??= Carbon::now();
        $next = $reference->copy()->addMonthNoOverflow();

        $period = Period::firstOrCreate([
            'year' => $next->year,
            'month' => $next->month,
        ]);

        Area::query()->where('active', true)->each(function (Area $area) use ($period) {
            MonthlyPlan::firstOrCreate([
                'period_id' => $period->id,
                'area_id' => $area->id,
            ]);
        });

        return $period;
    }
}
