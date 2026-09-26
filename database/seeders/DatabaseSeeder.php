<?php

namespace Database\Seeders;

use App\Services\Plans\PeriodOpeningService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AreaSeeder::class,
            DemoUserSeeder::class,
            TicketCatalogSeeder::class,
        ]);

        // En producción esto lo dispara el cron de fin de mes; en dev/demo
        // hace falta al menos un período con planes en borrador para que el
        // dashboard no quede vacío recién sembrada la base.
        app(PeriodOpeningService::class)->openNext();
    }
}
