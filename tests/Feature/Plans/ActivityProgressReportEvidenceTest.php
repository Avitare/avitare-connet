<?php

namespace Tests\Feature\Plans;

use App\Enums\ActivityProgressType;
use App\Models\Activity;
use App\Models\ActivityProgressReportAttachment;
use App\Models\Area;
use App\Models\Company;
use App\Models\MonthlyPlan;
use App\Models\Period;
use App\Models\PlanGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityProgressReportEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private Activity $activity;

    private User $jefe;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Role::findOrCreate('jefe_area');
        Role::findOrCreate('gerencia');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
        $period = Period::create(['year' => 2026, 'month' => 9, 'status' => 'abierto']);
        $plan = MonthlyPlan::create(['period_id' => $period->id, 'area_id' => $this->area->id, 'status' => 'borrador']);
        $group = PlanGroup::create(['monthly_plan_id' => $plan->id, 'name' => 'Grupo']);

        $this->activity = Activity::create([
            'plan_group_id' => $group->id,
            'responsible_name' => 'Responsable de prueba',
            'name' => 'Actividad',
            'progress_type' => ActivityProgressType::Porcentaje,
            'weight' => 1,
        ]);

        foreach ([1, 2, 3, 4] as $week) {
            $this->activity->weeks()->create(['week_number' => $week]);
        }

        $this->jefe = User::factory()->create(['area_id' => $this->area->id]);
        $this->jefe->assignRole('jefe_area');
    }

    public function test_reporting_progress_with_evidence_creates_the_attachments(): void
    {
        $this->actingAs($this->jefe)
            ->post(route('activity-progress-reports.store', $this->activity), [
                'value' => 50,
                'evidence' => [
                    UploadedFile::fake()->image('captura.jpg'),
                    UploadedFile::fake()->create('comprobante.pdf', 500, 'application/pdf'),
                ],
            ])
            ->assertSessionHasNoErrors();

        $report = $this->activity->progressReports()->latest('created_at')->first();

        $this->assertCount(2, $report->attachments);
        $this->assertEquals('captura.jpg', $report->attachments[0]->original_name);
        Storage::disk('local')->assertExists($report->attachments[0]->path);
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $this->actingAs($this->jefe)
            ->post(route('activity-progress-reports.store', $this->activity), [
                'value' => 50,
                'evidence' => [
                    UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
                ],
            ])
            ->assertSessionHasErrors('evidence.0');

        $this->assertSame(0, ActivityProgressReportAttachment::count());
    }

    public function test_more_than_five_files_is_rejected(): void
    {
        $files = array_map(
            fn ($i) => UploadedFile::fake()->image("foto{$i}.jpg"),
            range(1, 6),
        );

        $this->actingAs($this->jefe)
            ->post(route('activity-progress-reports.store', $this->activity), [
                'value' => 50,
                'evidence' => $files,
            ])
            ->assertSessionHasErrors('evidence');

        $this->assertSame(0, ActivityProgressReportAttachment::count());
    }

    public function test_same_area_user_can_download_an_attachment(): void
    {
        $report = $this->activity->progressReports()->create(['value' => 50, 'reported_by' => $this->jefe->id]);
        $attachment = $report->attachments()->create([
            'path' => 'evidence/test.jpg',
            'original_name' => 'captura.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);
        Storage::disk('local')->put('evidence/test.jpg', 'contenido');

        $this->actingAs($this->jefe)
            ->get(route('progress-report-attachments.download', $attachment))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=captura.jpg');
    }

    public function test_user_from_another_area_cannot_download_an_attachment(): void
    {
        $report = $this->activity->progressReports()->create(['value' => 50, 'reported_by' => $this->jefe->id]);
        $attachment = $report->attachments()->create([
            'path' => 'evidence/test.jpg',
            'original_name' => 'captura.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);
        Storage::disk('local')->put('evidence/test.jpg', 'contenido');

        $otherCompany = Company::create(['name' => 'Otra Empresa']);
        $otherArea = Area::create(['company_id' => $otherCompany->id, 'name' => 'Marketing', 'slug' => 'marketing', 'active' => true]);
        $outsider = User::factory()->create(['area_id' => $otherArea->id]);

        $this->actingAs($outsider)
            ->get(route('progress-report-attachments.download', $attachment))
            ->assertForbidden();
    }
}
