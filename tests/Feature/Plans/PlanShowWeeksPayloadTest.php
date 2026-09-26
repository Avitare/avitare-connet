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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanShowWeeksPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_exposes_total_weeks_and_per_activity_planned_weeks(): void
    {
        Role::findOrCreate('jefe_area');

        Carbon::setTestNow(Carbon::create(2026, 9, 10));

        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $area->id, 'status' => 'borrador']);
        $group = PlanGroup::create(['monthly_plan_id' => $plan->id, 'name' => 'Grupo']);

        $activity = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Responsable de prueba',
            'name' => 'Actividad salteada',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);
        $activity->weeks()->create(['week_number' => 1]);
        $activity->weeks()->create(['week_number' => 3]);

        $jefe = User::factory()->create(['area_id' => $area->id]);
        $jefe->assignRole('jefe_area');

        $response = $this->actingAs($jefe)->get(route('plans.show', $plan));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('plan.total_weeks', 5)
            ->where('plan.current_week', 2)
            ->where('groups.0.activities.0.weeks.0.week_number', 1)
            ->where('groups.0.activities.0.weeks.1.week_number', 3)
            ->has('groups.0.activities.0.weeks', 2)
        );
    }
}
