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
use App\Services\Plans\PlanCloningService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCloningServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanCloningService $service;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlanCloningService::class);

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
    }

    private function makeActivity(PlanGroup $group, array $overrides = []): Activity
    {
        $weekStart = $overrides['week_start'] ?? 1;
        $weekEnd = $overrides['week_end'] ?? 2;
        unset($overrides['week_start'], $overrides['week_end']);

        $activity = Activity::create(array_merge([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Responsable de prueba',
            'name' => 'Actividad',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ], $overrides));

        foreach (range($weekStart, $weekEnd) as $week) {
            $activity->weeks()->create(['week_number' => $week]);
        }

        return $activity->fresh();
    }

    public function test_clone_copies_groups_and_only_carries_over_incomplete_activities(): void
    {
        $septiembre = Period::create(['year' => 2026, 'month' => 9, 'status' => 'cerrado']);
        $source = MonthlyPlan::create(['period_id' => $septiembre->id, 'area_id' => $this->area->id, 'status' => 'cerrado']);

        $groupWithWork = PlanGroup::create(['monthly_plan_id' => $source->id, 'name' => 'Comercial', 'position' => 1]);
        $emptyGroup = PlanGroup::create(['monthly_plan_id' => $source->id, 'name' => 'Marketing', 'position' => 2]);

        $reporter = User::factory()->create(['area_id' => $this->area->id]);

        $completed = $this->makeActivity($groupWithWork, ['name' => 'Cotizaciones', 'week_start' => 1, 'week_end' => 2]);
        $completed->progressReports()->create(['value' => 100, 'reported_by' => $reporter->id]);

        $incomplete = $this->makeActivity($groupWithWork, ['name' => 'Visitas', 'week_start' => 1, 'week_end' => 2]);
        $incomplete->progressReports()->create(['value' => 30, 'reported_by' => $reporter->id]);

        Carbon::setTestNow(Carbon::create(2026, 9, 25));

        $octubre = Period::create(['year' => 2026, 'month' => 10, 'status' => 'abierto']);
        $target = MonthlyPlan::create(['period_id' => $octubre->id, 'area_id' => $this->area->id, 'status' => 'borrador']);

        $result = $this->service->cloneInto($target, $source);

        $this->assertEquals($source->id, $result->cloned_from_plan_id);
        $this->assertEquals(2, $result->groups()->count());

        $clonedComercial = $result->groups()->where('name', 'Comercial')->first();
        $clonedMarketing = $result->groups()->where('name', 'Marketing')->first();

        $this->assertNotNull($clonedMarketing);
        $this->assertEquals(0, $clonedMarketing->activities()->count());

        $this->assertEquals(1, $clonedComercial->activities()->count());
        $carried = $clonedComercial->activities()->first();
        $this->assertEquals('Visitas', $carried->name);
        $this->assertTrue($carried->carried_over);
        $this->assertEquals($incomplete->id, $carried->carried_over_from_id);
        $this->assertEquals([1, 2, 3, 4, 5], $carried->plannedWeekNumbers());
        $this->assertFalse($carried->added_after_approval);
    }

    public function test_clone_throws_when_target_plan_is_not_borrador(): void
    {
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $source = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $this->area->id, 'status' => 'cerrado']);

        $otherPeriod = Period::create(['year' => 2026, 'month' => 10, 'status' => 'abierto']);
        $target = MonthlyPlan::create(['period_id' => $otherPeriod->id, 'area_id' => $this->area->id, 'status' => 'vigente']);

        $this->expectException(DomainException::class);

        $this->service->cloneInto($target, $source);
    }

    public function test_clone_throws_when_target_plan_already_has_groups(): void
    {
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $source = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $this->area->id, 'status' => 'cerrado']);

        $otherPeriod = Period::create(['year' => 2026, 'month' => 10, 'status' => 'abierto']);
        $target = MonthlyPlan::create(['period_id' => $otherPeriod->id, 'area_id' => $this->area->id, 'status' => 'borrador']);
        PlanGroup::create(['monthly_plan_id' => $target->id, 'name' => 'Ya armado']);

        $this->expectException(DomainException::class);

        $this->service->cloneInto($target, $source);
    }
}
