<?php

namespace Tests\Feature\Plans;

use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanIndexPeriodFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerencia_sees_all_periods_by_default_and_can_filter_to_one(): void
    {
        Role::findOrCreate('gerencia');

        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);

        $septiembre = Period::create(['year' => 2026, 'month' => 9, 'status' => 'cerrado']);
        $octubre = Period::create(['year' => 2026, 'month' => 10, 'status' => 'abierto']);

        $planSeptiembre = MonthlyPlan::create(['period_id' => $septiembre->id, 'area_id' => $area->id, 'status' => 'cerrado']);
        $planOctubre = MonthlyPlan::create(['period_id' => $octubre->id, 'area_id' => $area->id, 'status' => 'borrador']);

        $gerente = User::factory()->create();
        $gerente->assignRole('gerencia');

        $responseAll = $this->actingAs($gerente)->get(route('plans.index'));
        $responseAll->assertOk();
        $responseAll->assertInertia(fn ($page) => $page
            ->has('plans', 2)
            ->where('selectedPeriodId', null)
        );

        $responseFiltered = $this->actingAs($gerente)->get(route('plans.index', ['period_id' => $septiembre->id]));
        $responseFiltered->assertInertia(fn ($page) => $page
            ->has('plans', 1)
            ->where('plans.0.id', $planSeptiembre->id)
            ->where('selectedPeriodId', $septiembre->id)
        );

        // Confirma que el filtro realmente excluye el otro período.
        $this->assertNotEquals($planOctubre->id, $planSeptiembre->id);
    }
}
