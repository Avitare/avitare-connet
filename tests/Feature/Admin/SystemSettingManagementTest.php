<?php

namespace Tests\Feature\Admin;

use App\Models\Area;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemSettingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $jefe;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_area');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);

        $this->jefe = User::factory()->create(['area_id' => $area->id]);
        $this->jefe->assignRole('jefe_area');
    }

    public function test_settings_default_to_all_rules_enabled(): void
    {
        $settings = SystemSetting::current();

        $this->assertTrue($settings->require_deliverable_to_close_activity);
        $this->assertTrue($settings->require_weeks_completed_to_mark_activity_done);
        $this->assertSame(80, $settings->activity_risk_threshold);
    }

    public function test_admin_can_view_the_settings_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit'))
            ->assertOk();
    }

    public function test_admin_can_update_the_settings(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'require_deliverable_to_close_activity' => false,
                'require_weeks_completed_to_mark_activity_done' => false,
                'activity_risk_threshold' => 70,
            ])
            ->assertSessionHasNoErrors();

        $settings = SystemSetting::current();
        $this->assertFalse($settings->require_deliverable_to_close_activity);
        $this->assertFalse($settings->require_weeks_completed_to_mark_activity_done);
        $this->assertSame(70, $settings->activity_risk_threshold);
    }

    public function test_jefe_de_area_cannot_view_or_update_settings(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();

        $this->actingAs($this->jefe)
            ->put(route('admin.settings.update'), [
                'require_deliverable_to_close_activity' => false,
                'require_weeks_completed_to_mark_activity_done' => false,
                'activity_risk_threshold' => 70,
            ])
            ->assertForbidden();
    }
}
