<?php

namespace Tests\Feature\Plans;

use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\PlanGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use RefreshDatabase;

    private MonthlyPlan $plan;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('jefe_area');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $this->plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $this->area->id, 'status' => 'borrador']);
    }

    private function jefe(?int $areaId = null): User
    {
        $user = User::factory()->create(['area_id' => $areaId ?? $this->area->id]);
        $user->assignRole('jefe_area');

        return $user;
    }

    public function test_jefe_de_area_can_create_a_group_in_their_own_area_plan(): void
    {
        $this->actingAs($this->jefe())
            ->post(route('plan-groups.store', $this->plan), ['name' => 'Cartas de no adeudo'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('plan_groups', [
            'monthly_plan_id' => $this->plan->id,
            'name' => 'Cartas de no adeudo',
        ]);
    }

    public function test_jefe_de_area_can_create_an_activity_with_a_free_text_responsible_name(): void
    {
        $group = PlanGroup::create(['monthly_plan_id' => $this->plan->id, 'name' => 'Grupo']);

        $this->actingAs($this->jefe())
            ->post(route('activities.store', $group), [
                'name' => 'Conciliación de pagos',
                'responsible_name' => 'María Alejandra',
                'weeks' => [1, 2, 3, 4],
                'progress_type' => 'porcentaje',
                'weight' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activities', [
            'plan_group_id' => $group->id,
            'name' => 'Conciliación de pagos',
            'responsible_name' => 'María Alejandra',
        ]);
    }

    public function test_jefe_de_area_from_another_area_cannot_manage_this_plan(): void
    {
        $company = Company::create(['name' => 'Otra Empresa']);
        $otherArea = Area::create(['company_id' => $company->id, 'name' => 'Marketing', 'slug' => 'marketing', 'active' => true]);

        $this->actingAs($this->jefe($otherArea->id))
            ->post(route('plan-groups.store', $this->plan), ['name' => 'No debería poder'])
            ->assertForbidden();
    }

    public function test_management_is_blocked_once_the_plan_is_closed(): void
    {
        $this->plan->update(['status' => 'cerrado']);
        $group = PlanGroup::create(['monthly_plan_id' => $this->plan->id, 'name' => 'Grupo']);

        $this->actingAs($this->jefe())
            ->post(route('activities.store', $group), [
                'name' => 'No debería poder',
                'responsible_name' => 'Alguien',
                'weeks' => [1, 2, 3, 4],
                'progress_type' => 'porcentaje',
                'weight' => 1,
            ])
            ->assertForbidden();
    }
}
