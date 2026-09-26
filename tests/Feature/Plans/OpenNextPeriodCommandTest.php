<?php

namespace Tests\Feature\Plans;

use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenNextPeriodCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_next_period_with_a_draft_plan_per_active_area(): void
    {
        $company = Company::create(['name' => 'Empresa']);
        $active = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        Area::create(['company_id' => $company->id, 'name' => 'Inactiva', 'slug' => 'inactiva', 'active' => false]);

        Carbon::setTestNow(Carbon::create(2026, 9, 19));

        $this->artisan('period:open-next')->assertSuccessful();

        $period = Period::where('year', 2026)->where('month', 10)->first();

        $this->assertNotNull($period);
        $this->assertEquals(1, MonthlyPlan::where('period_id', $period->id)->count());
        $this->assertDatabaseHas('monthly_plans', [
            'period_id' => $period->id,
            'area_id' => $active->id,
            'status' => 'borrador',
        ]);
    }

    public function test_running_it_twice_does_not_duplicate_periods_or_plans(): void
    {
        $company = Company::create(['name' => 'Empresa']);
        Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);

        Carbon::setTestNow(Carbon::create(2026, 9, 19));

        $this->artisan('period:open-next')->assertSuccessful();
        $this->artisan('period:open-next')->assertSuccessful();

        $this->assertEquals(1, Period::where('year', 2026)->where('month', 10)->count());
        $this->assertEquals(1, MonthlyPlan::count());
    }
}
