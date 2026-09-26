<?php

namespace Tests\Feature\Plans;

use App\Enums\ActivityProgressType;
use App\Models\Activity;
use App\Models\ActivityWeek;
use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\PlanGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityWeekToggleTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private MonthlyPlan $plan;

    private ActivityWeek $week;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('jefe_area');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $this->plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $this->area->id, 'status' => 'borrador']);
        $group = PlanGroup::create(['monthly_plan_id' => $this->plan->id, 'name' => 'Grupo']);

        $activity = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Responsable de prueba',
            'name' => 'Actividad',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);

        $this->week = $activity->weeks()->create(['week_number' => 1]);
    }

    private function jefe(?int $areaId = null): User
    {
        $user = User::factory()->create(['area_id' => $areaId ?? $this->area->id]);
        $user->assignRole('jefe_area');

        return $user;
    }

    public function test_jefe_de_area_can_mark_a_planned_week_as_completed(): void
    {
        $this->actingAs($this->jefe())
            ->patch(route('activity-weeks.toggle', $this->week))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($this->week->fresh()->completed_at);
    }

    public function test_toggling_twice_clears_the_completed_mark(): void
    {
        $jefe = $this->jefe();

        $this->actingAs($jefe)->patch(route('activity-weeks.toggle', $this->week));
        $this->actingAs($jefe)->patch(route('activity-weeks.toggle', $this->week));

        $this->assertNull($this->week->fresh()->completed_at);
    }

    public function test_jefe_de_area_from_another_area_cannot_toggle(): void
    {
        $company = Company::create(['name' => 'Otra Empresa']);
        $otherArea = Area::create(['company_id' => $company->id, 'name' => 'Marketing', 'slug' => 'marketing', 'active' => true]);

        $this->actingAs($this->jefe($otherArea->id))
            ->patch(route('activity-weeks.toggle', $this->week))
            ->assertForbidden();
    }

    public function test_toggle_is_blocked_once_the_plan_is_closed(): void
    {
        $this->plan->update(['status' => 'cerrado']);

        $this->actingAs($this->jefe())
            ->patch(route('activity-weeks.toggle', $this->week))
            ->assertForbidden();
    }
}
