<?php

namespace Tests\Feature;

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

class DashboardDueSoonTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_due_soon_lists_activities_ending_this_week_or_next_but_not_further_or_completed(): void
    {
        // 2026-09-15 cae en la semana 3 de septiembre 2026 (5 semanas ese mes).
        Carbon::setTestNow(Carbon::create(2026, 9, 15));

        Role::findOrCreate('jefe_area');

        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $area->id, 'status' => 'vigente']);
        $group = PlanGroup::create(['monthly_plan_id' => $plan->id, 'name' => 'Grupo']);

        $jefe = User::factory()->create(['area_id' => $area->id]);
        $jefe->assignRole('jefe_area');

        $vencePuntual = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Vence esta semana',
            'name' => 'Vence esta semana',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);
        $vencePuntual->weeks()->create(['week_number' => 3]);

        $venceProxima = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Vence la próxima semana',
            'name' => 'Vence la próxima semana',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);
        $venceProxima->weeks()->create(['week_number' => 4]);

        $lejos = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Todavía lejos',
            'name' => 'Todavía lejos',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);
        $lejos->weeks()->create(['week_number' => 5]);

        $yaCompletada = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Ya completada',
            'name' => 'Ya completada',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);
        $yaCompletada->weeks()->create(['week_number' => 3]);
        $yaCompletada->progressReports()->create(['value' => 100, 'reported_by' => $jefe->id]);

        $this->actingAs($jefe)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('myPlan.current_week', 3)
                ->where('myPlan.due_soon', fn ($dueSoon) => collect($dueSoon)->pluck('id')->sort()->values()->all()
                    === collect([$vencePuntual->id, $venceProxima->id])->sort()->values()->all())
            );
    }
}
