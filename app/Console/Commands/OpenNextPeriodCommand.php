<?php

namespace App\Console\Commands;

use App\Services\Plans\PeriodOpeningService;
use Illuminate\Console\Command;

class OpenNextPeriodCommand extends Command
{
    protected $signature = 'period:open-next';

    protected $description = 'Crea el período siguiente y un plan en borrador por cada área activa';

    public function handle(PeriodOpeningService $service): int
    {
        $period = $service->openNext();

        $this->info("Período {$period->year}-{$period->month} listo con planes en borrador.");

        return self::SUCCESS;
    }
}
