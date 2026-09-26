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
use App\Services\Plans\ComplianceAggregationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ComplianceAggregationService $service;

    private Period $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ComplianceAggregationService::class);
        $this->period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);

        Carbon::setTestNow(Carbon::create(2026, 9, 25));
    }

    private function createGroup(Area $area): PlanGroup
    {
        // 'borrador' para que Activity::booted() no fuerce added_after_approval por su cuenta;
        // estos tests lo controlan explícitamente vía el parámetro $addedAfterApproval.
        $plan = MonthlyPlan::create(['period_id' => $this->period->id, 'area_id' => $area->id, 'status' => 'borrador']);

        return PlanGroup::create(['monthly_plan_id' => $plan->id, 'name' => 'Grupo']);
    }

    private function createActivity(PlanGroup $group, User $reporter, float $weight, float $reportedValue, bool $addedAfterApproval = false): Activity
    {
        $activity = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Responsable de prueba',
            'name' => 'Actividad',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => $weight,
            'added_after_approval' => $addedAfterApproval,
        ]);

        foreach ([1, 2] as $week) {
            $activity->weeks()->create(['week_number' => $week]);
        }

        $activity->progressReports()->create(['value' => $reportedValue, 'reported_by' => $reporter->id]);

        return $activity;
    }

    public function test_group_aggregate_excludes_activities_added_after_approval(): void
    {
        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $group = $this->createGroup($area);
        $user = User::factory()->create(['area_id' => $area->id]);

        // week 1-2, reference en semana 4 => esperado 100, real 50 => cumplimiento 50.
        $this->createActivity($group, $user, weight: 1, reportedValue: 50);
        // peso alto pero es "extra": no debe mover el promedio.
        $this->createActivity($group, $user, weight: 3, reportedValue: 10, addedAfterApproval: true);

        $this->assertEquals(50.0, $this->service->forGroup($group));
    }

    public function test_plan_aggregate_is_weighted_by_activity_weight(): void
    {
        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $group = $this->createGroup($area);
        $user = User::factory()->create(['area_id' => $area->id]);

        $this->createActivity($group, $user, weight: 1, reportedValue: 100); // cumplimiento 100
        $this->createActivity($group, $user, weight: 3, reportedValue: 0);   // cumplimiento 0

        $plan = $group->monthlyPlan;

        // (100*1 + 0*3) / 4 = 25
        $this->assertEquals(25.0, $this->service->forPlan($plan));
    }

    public function test_company_aggregate_combines_all_its_areas_and_ignores_other_companies(): void
    {
        $companyA = Company::create(['name' => 'Empresa A']);
        $ventas = Area::create(['company_id' => $companyA->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $marketing = Area::create(['company_id' => $companyA->id, 'name' => 'Marketing', 'slug' => 'marketing', 'active' => true]);

        $groupVentas = $this->createGroup($ventas);
        $groupMarketing = $this->createGroup($marketing);

        $userVentas = User::factory()->create(['area_id' => $ventas->id]);
        $userMarketing = User::factory()->create(['area_id' => $marketing->id]);

        $this->createActivity($groupVentas, $userVentas, weight: 1, reportedValue: 50);
        $this->createActivity($groupMarketing, $userMarketing, weight: 1, reportedValue: 100);

        $companyB = Company::create(['name' => 'Empresa B']);
        $otraArea = Area::create(['company_id' => $companyB->id, 'name' => 'Otra', 'slug' => 'otra', 'active' => true]);
        $groupOtra = $this->createGroup($otraArea);
        $userOtro = User::factory()->create(['area_id' => $otraArea->id]);
        $this->createActivity($groupOtra, $userOtro, weight: 1, reportedValue: 0);

        // (50 + 100) / 2 = 75, sin contar a la Empresa B.
        $this->assertEquals(75.0, $this->service->forCompany($companyA, $this->period));
    }

    public function test_returns_null_when_there_are_no_official_activities(): void
    {
        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $group = $this->createGroup($area);
        $user = User::factory()->create(['area_id' => $area->id]);

        $this->createActivity($group, $user, weight: 1, reportedValue: 10, addedAfterApproval: true);

        $this->assertNull($this->service->forGroup($group));
    }

    public function test_plan_weekly_compliance_reconstructs_real_value_at_each_week_cutoff(): void
    {
        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $group = $this->createGroup($area);
        $user = User::factory()->create(['area_id' => $area->id]);

        // Actividad de la semana 1 a la 4, meta 100. Se reporta progresivamente
        // a lo largo del mes: cada reporte queda "congelado" en su semana.
        $activity = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Responsable de prueba',
            'name' => 'Actividad',
            'progress_type' => \App\Enums\ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);

        foreach ([1, 2, 3, 4] as $week) {
            $activity->weeks()->create(['week_number' => $week]);
        }

        $this->report($activity, $user, 25, Carbon::create(2026, 9, 5));  // dentro de semana 1
        $this->report($activity, $user, 50, Carbon::create(2026, 9, 12)); // dentro de semana 2
        $this->report($activity, $user, 100, Carbon::create(2026, 9, 25)); // dentro de semana 4

        $plan = $group->monthlyPlan;
        $weekly = $this->service->planWeeklyCompliance($plan, $this->period);

        // Semana 1: esperado 25 (100 * 1/4), real 25 (último reporte al día 5) => 100%.
        $this->assertEquals(100.0, $weekly[1]);
        // Semana 2: esperado 50 (100 * 2/4), real 50 (reporte del día 12) => 100%.
        $this->assertEquals(100.0, $weekly[2]);
        // Semana 3: esperado 75, real sigue siendo 50 (no hay reporte nuevo todavía) => 66.67%.
        $this->assertEquals(66.67, $weekly[3]);
        // Semana 4: esperado 100, real 100 (reporte del día 25) => 100%.
        $this->assertEquals(100.0, $weekly[4]);
    }

    private function report(Activity $activity, User $user, float $value, Carbon $date): void
    {
        $report = $activity->progressReports()->create(['value' => $value, 'reported_by' => $user->id]);
        $report->forceFill(['created_at' => $date])->save();
    }
}
