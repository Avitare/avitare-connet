<?php

namespace Tests\Feature\Plans;

use App\Enums\ActivityProgressType;
use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\PlanGroup;
use App\Models\User;
use App\Services\Plans\ActivityMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ActivityMetricsService();
    }

    private function makeActivity(array $overrides = []): Activity
    {
        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $area->id, 'status' => 'vigente']);
        $group = PlanGroup::create(['monthly_plan_id' => $plan->id, 'name' => 'Comercial']);

        $weekStart = $overrides['week_start'] ?? 1;
        $weekEnd = $overrides['week_end'] ?? 4;
        unset($overrides['week_start'], $overrides['week_end']);

        $activity = Activity::create(array_merge([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Responsable de prueba',
            'name' => 'Cotizaciones',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ], $overrides));

        foreach (range($weekStart, $weekEnd) as $week) {
            $activity->weeks()->create(['week_number' => $week]);
        }

        return $activity->fresh();
    }

    public function test_expected_to_date_is_zero_before_the_activity_starts(): void
    {
        $activity = $this->makeActivity(['week_start' => 3, 'week_end' => 4]);

        Carbon::setTestNow(Carbon::create(2026, 9, 5));

        $this->assertEquals(0.0, $this->service->expectedToDate($activity));
    }

    public function test_expected_to_date_grows_linearly_across_the_activity_window(): void
    {
        $activity = $this->makeActivity(['week_start' => 1, 'week_end' => 4]);

        Carbon::setTestNow(Carbon::create(2026, 9, 10));

        $this->assertEquals(50.0, $this->service->expectedToDate($activity));
    }

    public function test_expected_to_date_caps_at_target_after_the_activity_window(): void
    {
        $activity = $this->makeActivity(['week_start' => 1, 'week_end' => 2]);

        Carbon::setTestNow(Carbon::create(2026, 9, 25));

        $this->assertEquals(100.0, $this->service->expectedToDate($activity));
    }

    public function test_real_value_is_the_latest_progress_report(): void
    {
        $activity = $this->makeActivity();
        $reporter = User::factory()->create(['area_id' => $activity->planGroup->monthlyPlan->area_id]);

        $activity->progressReports()->create(['value' => 20, 'reported_by' => $reporter->id]);
        $activity->progressReports()->create(['value' => 45, 'reported_by' => $reporter->id]);

        $this->assertEquals(45.0, $this->service->realValue($activity->fresh()));
    }

    public function test_compliance_is_capped_at_100_percent(): void
    {
        $activity = $this->makeActivity(['week_start' => 1, 'week_end' => 4]);
        $reporter = User::factory()->create(['area_id' => $activity->planGroup->monthlyPlan->area_id]);
        $activity->progressReports()->create(['value' => 100, 'reported_by' => $reporter->id]);

        Carbon::setTestNow(Carbon::create(2026, 9, 10));

        $this->assertEquals(100.0, $this->service->compliance($activity->fresh()));
    }

    public function test_status_is_completada_when_real_reaches_the_target(): void
    {
        $activity = $this->makeActivity(['week_start' => 1, 'week_end' => 4]);
        $reporter = User::factory()->create(['area_id' => $activity->planGroup->monthlyPlan->area_id]);
        $activity->progressReports()->create(['value' => 100, 'reported_by' => $reporter->id]);

        Carbon::setTestNow(Carbon::create(2026, 9, 10));

        $this->assertEquals(ActivityStatus::Completada, $this->service->status($activity->fresh()));
    }

    public function test_status_is_por_iniciar_before_the_activity_window_starts(): void
    {
        $activity = $this->makeActivity(['week_start' => 3, 'week_end' => 4]);

        Carbon::setTestNow(Carbon::create(2026, 9, 5));

        $this->assertEquals(ActivityStatus::PorIniciar, $this->service->status($activity));
    }

    public function test_status_is_atrasada_after_the_window_ends_without_completing(): void
    {
        $activity = $this->makeActivity(['week_start' => 1, 'week_end' => 2]);

        Carbon::setTestNow(Carbon::create(2026, 9, 25));

        $this->assertEquals(ActivityStatus::Atrasada, $this->service->status($activity));
    }

    public function test_status_is_en_riesgo_when_compliance_is_low_mid_window(): void
    {
        $activity = $this->makeActivity(['week_start' => 1, 'week_end' => 4]);
        $reporter = User::factory()->create(['area_id' => $activity->planGroup->monthlyPlan->area_id]);
        $activity->progressReports()->create(['value' => 10, 'reported_by' => $reporter->id]);

        Carbon::setTestNow(Carbon::create(2026, 9, 25));

        $this->assertEquals(ActivityStatus::EnRiesgo, $this->service->status($activity->fresh()));
    }

    public function test_numeric_goal_progress_type_uses_its_own_target(): void
    {
        $activity = $this->makeActivity([
            'progress_type' => ActivityProgressType::MetaNumerica,
            'numeric_goal_target' => 30,
            'week_start' => 1,
            'week_end' => 4,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 9, 10));

        $this->assertEquals(15.0, $this->service->expectedToDate($activity));
    }
}
