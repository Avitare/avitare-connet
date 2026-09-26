<?php

namespace Tests\Feature\Plans;

use App\Enums\ActivityProgressType;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\PlanGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityCloseTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private MonthlyPlan $plan;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('jefe_area');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $this->plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $this->area->id, 'status' => 'vigente']);
        $group = PlanGroup::create(['monthly_plan_id' => $this->plan->id, 'name' => 'Grupo']);

        $this->activity = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Responsable de prueba',
            'name' => 'Actividad',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);
    }

    private function jefe(?int $areaId = null): User
    {
        $user = User::factory()->create(['area_id' => $areaId ?? $this->area->id]);
        $user->assignRole('jefe_area');

        return $user;
    }

    public function test_jefe_de_area_can_close_an_activity_without_closing_the_plan(): void
    {
        $jefe = $this->jefe();

        $this->actingAs($jefe)
            ->post(route('activities.close', $this->activity))
            ->assertSessionHasNoErrors();

        $this->activity->refresh();
        $this->plan->refresh();

        $this->assertNotNull($this->activity->closed_at);
        $this->assertEquals($jefe->id, $this->activity->closed_by);
        $this->assertEquals('vigente', $this->plan->status->value);
    }

    public function test_reporting_on_a_closed_activity_is_forbidden(): void
    {
        $jefe = $this->jefe();
        $this->activity->update(['closed_at' => now(), 'closed_by' => $jefe->id]);

        $this->actingAs($jefe)
            ->post(route('activity-progress-reports.store', $this->activity), ['completed' => true])
            ->assertForbidden();
    }

    public function test_reopening_a_closed_activity_allows_reporting_again(): void
    {
        $jefe = $this->jefe();
        $this->activity->update(['closed_at' => now(), 'closed_by' => $jefe->id]);

        $this->actingAs($jefe)
            ->post(route('activities.reopen', $this->activity))
            ->assertSessionHasNoErrors();

        $this->assertNull($this->activity->fresh()->closed_at);

        $this->actingAs($jefe)
            ->post(route('activity-progress-reports.store', $this->activity), ['completed' => true])
            ->assertSessionHasNoErrors();
    }

    public function test_jefe_de_area_from_another_area_cannot_close(): void
    {
        $company = Company::create(['name' => 'Otra Empresa']);
        $otherArea = Area::create(['company_id' => $company->id, 'name' => 'Marketing', 'slug' => 'marketing', 'active' => true]);

        $this->actingAs($this->jefe($otherArea->id))
            ->post(route('activities.close', $this->activity))
            ->assertForbidden();
    }

    public function test_closing_an_activity_is_blocked_once_the_plan_is_closed(): void
    {
        $this->plan->update(['status' => 'cerrado']);

        $this->actingAs($this->jefe())
            ->post(route('activities.close', $this->activity))
            ->assertForbidden();
    }

    public function test_creating_new_activities_still_works_while_another_one_is_closed(): void
    {
        $jefe = $this->jefe();
        $this->activity->update(['closed_at' => now(), 'closed_by' => $jefe->id]);

        $this->actingAs($jefe)
            ->post(route('activities.store', $this->activity->plan_group_id), [
                'name' => 'Otra actividad',
                'responsible_name' => 'Otro responsable',
                'weeks' => [1],
                'progress_type' => 'porcentaje',
                'weight' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activities', [
            'plan_group_id' => $this->activity->plan_group_id,
            'name' => 'Otra actividad',
        ]);
    }
}
