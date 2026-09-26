<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardPeriodFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerencia_can_switch_the_dashboard_to_a_past_period(): void
    {
        Role::findOrCreate('gerencia');

        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);

        $septiembre = Period::create(['year' => 2026, 'month' => 9, 'status' => 'cerrado']);
        $octubre = Period::create(['year' => 2026, 'month' => 10, 'status' => 'abierto']);

        MonthlyPlan::create(['period_id' => $septiembre->id, 'area_id' => $area->id, 'status' => 'cerrado']);
        MonthlyPlan::create(['period_id' => $octubre->id, 'area_id' => $area->id, 'status' => 'borrador']);

        $gerente = User::factory()->create();
        $gerente->assignRole('gerencia');

        // Por defecto trae el período más reciente (octubre).
        $this->actingAs($gerente)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('period.id', $octubre->id)
                ->where('areasOverview.0.status', 'borrador')
            );

        // Pasando period_id trae el snapshot de septiembre, no el más reciente.
        $this->actingAs($gerente)
            ->get(route('dashboard', ['period_id' => $septiembre->id]))
            ->assertInertia(fn ($page) => $page
                ->where('period.id', $septiembre->id)
                ->where('areasOverview.0.status', 'cerrado')
            );
    }
}
