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

class PlanCreationTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('jefe_area');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
    }

    private function jefe(): User
    {
        $user = User::factory()->create(['area_id' => $this->area->id]);
        $user->assignRole('jefe_area');

        return $user;
    }

    public function test_jefe_de_area_can_create_their_plan_when_a_period_already_exists(): void
    {
        $period = Period::create(['year' => 2026, 'month' => 10, 'status' => 'abierto']);

        $this->actingAs($this->jefe())
            ->post(route('plans.create-mine'))
            ->assertRedirect();

        $this->assertDatabaseHas('monthly_plans', [
            'period_id' => $period->id,
            'area_id' => $this->area->id,
            'status' => 'borrador',
        ]);
    }

    public function test_jefe_de_area_can_create_their_plan_when_no_period_exists_yet(): void
    {
        $this->assertSame(0, Period::count());

        $this->actingAs($this->jefe())
            ->post(route('plans.create-mine'))
            ->assertRedirect();

        $this->assertSame(1, Period::count());
        $this->assertDatabaseHas('monthly_plans', ['area_id' => $this->area->id]);
    }

    public function test_creating_the_plan_twice_does_not_duplicate_it(): void
    {
        Period::create(['year' => 2026, 'month' => 10, 'status' => 'abierto']);

        $this->actingAs($this->jefe())->post(route('plans.create-mine'));
        $this->actingAs($this->jefe())->post(route('plans.create-mine'));

        $this->assertSame(1, MonthlyPlan::where('area_id', $this->area->id)->count());
    }

    public function test_non_jefe_area_cannot_create_a_plan(): void
    {
        $user = User::factory()->create(['area_id' => $this->area->id]);

        $this->actingAs($user)
            ->post(route('plans.create-mine'))
            ->assertForbidden();
    }

    public function test_jefe_de_area_can_create_a_plan_for_any_month_by_year_and_month(): void
    {
        $this->assertSame(0, Period::count());

        $this->actingAs($this->jefe())
            ->post(route('plans.create-mine'), ['year' => 2027, 'month' => 3])
            ->assertRedirect();

        $this->assertDatabaseHas('periods', ['year' => 2027, 'month' => 3]);
        $period = Period::where('year', 2027)->where('month', 3)->first();

        $this->assertDatabaseHas('monthly_plans', [
            'period_id' => $period->id,
            'area_id' => $this->area->id,
        ]);
    }

    public function test_jefe_de_area_can_choose_which_open_period_to_create_the_plan_for(): void
    {
        $september = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $october = Period::create(['year' => 2026, 'month' => 10, 'status' => 'abierto']);

        $this->actingAs($this->jefe())
            ->post(route('plans.create-mine'), ['period_id' => $september->id])
            ->assertRedirect();

        $this->assertDatabaseHas('monthly_plans', [
            'period_id' => $september->id,
            'area_id' => $this->area->id,
        ]);
        $this->assertDatabaseMissing('monthly_plans', [
            'period_id' => $october->id,
            'area_id' => $this->area->id,
        ]);
    }
}
