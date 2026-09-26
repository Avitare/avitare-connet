<?php

namespace Tests\Feature\Plans;

use App\Enums\ActivityProgressType;
use App\Models\Activity;
use App\Models\ActivityProgressReport;
use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\PlanGroup;
use App\Models\User;
use App\Services\Plans\PlanClosingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanClosingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanClosingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlanClosingService::class);
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

    private function reportAt(Activity $activity, User $reporter, float $value, Carbon $date): ActivityProgressReport
    {
        $report = $activity->progressReports()->create(['value' => $value, 'reported_by' => $reporter->id]);
        $report->forceFill(['created_at' => $date])->save();

        return $report;
    }

    public function test_close_computes_the_four_final_numbers_and_transitions_to_cerrado(): void
    {
        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $area->id, 'status' => 'borrador']);
        $group = PlanGroup::create(['monthly_plan_id' => $plan->id, 'name' => 'Grupo']);
        $user = User::factory()->create(['area_id' => $area->id]);

        // Oficiales: creadas mientras el plan está en borrador, antes de congelarse.
        // Oficial: completada dentro de su semana -> puntual.
        $onTime = $this->makeActivity($group, ['week_start' => 1, 'week_end' => 2, 'weight' => 1]);
        $this->reportAt($onTime, $user, 100, Carbon::create(2026, 9, 10));

        // Oficial: completada, pero fuera de su semana -> no puntual.
        $late = $this->makeActivity($group, ['week_start' => 1, 'week_end' => 2, 'weight' => 1]);
        $this->reportAt($late, $user, 100, Carbon::create(2026, 9, 20));

        // Oficial: nunca reportada -> no completada, no puntual.
        $this->makeActivity($group, ['week_start' => 1, 'week_end' => 4, 'weight' => 2]);

        // El plan se aprueba/congela: de aquí en más, lo nuevo es "extra".
        $plan->update(['status' => 'vigente', 'frozen_at' => Carbon::create(2026, 9, 3)]);

        // Extra (agregada tras la aprobación): el hook la marca sola, no debe entrar a lo oficial.
        $extra = $this->makeActivity($group->fresh(), ['week_start' => 1, 'week_end' => 2, 'weight' => 5]);
        $this->reportAt($extra, $user, 100, Carbon::create(2026, 9, 10));
        $this->assertTrue($extra->added_after_approval);

        $closer = User::factory()->create(['area_id' => $area->id]);
        $reference = Carbon::create(2026, 9, 25);

        $result = $this->service->close($plan, $closer, $reference, 'Cierre de mes');

        $this->assertEquals('cerrado', $result->status->value);
        $this->assertEquals($closer->id, $result->closed_by);
        $this->assertTrue($result->closed_at->equalTo($reference));

        // (100*1 + 100*1 + 0*2) / 4 = 50
        $this->assertEquals(50.0, (float) $result->final_compliance_plan_aprobado);
        // + extra: (200 + 100*5) / 9 = 77.78
        $this->assertEquals(77.78, (float) $result->final_compliance_total_mes);
        // completadas ponderadas: (100 + 100 + 0*2) / 4 = 50
        $this->assertEquals(50.0, (float) $result->final_effectiveness);
        // puntuales ponderadas: solo $onTime -> (100 + 0 + 0*2) / 4 = 25
        $this->assertEquals(25.0, (float) $result->final_punctuality);

        $this->assertDatabaseHas('plan_status_logs', [
            'monthly_plan_id' => $plan->id,
            'from_status' => 'vigente',
            'to_status' => 'cerrado',
            'performed_by' => $closer->id,
            'reason' => 'Cierre de mes',
        ]);
    }

    public function test_close_throws_when_the_plan_is_not_vigente(): void
    {
        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Ventas', 'slug' => 'ventas', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $area->id, 'status' => 'borrador']);
        $closer = User::factory()->create(['area_id' => $area->id]);

        $this->expectException(DomainException::class);

        $this->service->close($plan, $closer);
    }
}
