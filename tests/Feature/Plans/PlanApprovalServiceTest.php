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
use App\Services\Plans\PlanApprovalService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlanApprovalService::class);
    }

    private function makePlan(): MonthlyPlan
    {
        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);

        return MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $area->id, 'status' => 'borrador']);
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

    public function test_approve_transitions_the_plan_to_vigente_and_freezes_it(): void
    {
        $plan = $this->makePlan();
        $approver = User::factory()->create(['area_id' => $plan->area_id]);
        $reference = Carbon::create(2026, 9, 1, 10);

        $result = $this->service->approve($plan, $approver, $reference, 'Plan revisado con el equipo');

        $this->assertEquals('vigente', $result->status->value);
        $this->assertEquals($approver->id, $result->approved_by);
        $this->assertTrue($result->approved_at->equalTo($reference));
        $this->assertTrue($result->frozen_at->equalTo($reference));

        $this->assertDatabaseHas('plan_status_logs', [
            'monthly_plan_id' => $plan->id,
            'from_status' => 'borrador',
            'to_status' => 'vigente',
            'performed_by' => $approver->id,
            'reason' => 'Plan revisado con el equipo',
        ]);
    }

    public function test_approve_throws_when_the_plan_is_not_in_borrador(): void
    {
        $plan = $this->makePlan();
        $plan->update(['status' => 'vigente']);
        $approver = User::factory()->create(['area_id' => $plan->area_id]);

        $this->expectException(DomainException::class);

        $this->service->approve($plan, $approver);
    }

    public function test_activities_created_before_approval_are_not_flagged_as_extra(): void
    {
        $plan = $this->makePlan();
        $group = PlanGroup::create(['monthly_plan_id' => $plan->id, 'name' => 'Grupo']);

        $activity = $this->makeActivity($group);

        $this->assertFalse($activity->added_after_approval);
    }

    public function test_activities_created_after_approval_are_flagged_as_extra_automatically(): void
    {
        $plan = $this->makePlan();
        $group = PlanGroup::create(['monthly_plan_id' => $plan->id, 'name' => 'Grupo']);
        $approver = User::factory()->create(['area_id' => $plan->area_id]);

        $this->service->approve($plan, $approver, Carbon::create(2026, 9, 1));

        $extra = $this->makeActivity($group->fresh());

        $this->assertTrue($extra->added_after_approval);
    }
}
