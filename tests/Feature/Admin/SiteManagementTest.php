<?php

namespace Tests\Feature\Admin;

use App\Models\Area;
use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteManagementTest extends TestCase
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

    public function test_admin_can_create_a_site(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.sites.store'), [
                'name' => 'Reportes',
                'description' => 'Panel de reportes internos.',
                'url' => 'https://reportes.grupoavitare.com/',
            ])
            ->assertRedirect(route('admin.sites.index'));

        $this->assertDatabaseHas('sites', [
            'name' => 'Reportes',
            'url' => 'https://reportes.grupoavitare.com/',
            'active' => true,
        ]);
    }

    public function test_creating_a_site_requires_a_valid_url(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.sites.store'), [
                'name' => 'Reportes',
                'url' => 'no-es-una-url',
            ])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseMissing('sites', ['name' => 'Reportes']);
    }

    public function test_admin_can_edit_a_site(): void
    {
        $site = Site::create(['name' => 'Reportes', 'url' => 'https://reportes.grupoavitare.com/']);

        $this->actingAs($this->admin)
            ->put(route('admin.sites.update', $site), [
                'name' => 'Reportes internos',
                'description' => 'Actualizado',
                'url' => 'https://reportes.grupoavitare.com/v2',
            ])
            ->assertRedirect(route('admin.sites.index'));

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'Reportes internos',
            'url' => 'https://reportes.grupoavitare.com/v2',
        ]);
    }

    public function test_admin_can_toggle_a_site_active_state(): void
    {
        $site = Site::create(['name' => 'Reportes', 'url' => 'https://reportes.grupoavitare.com/']);

        $this->actingAs($this->admin)
            ->patch(route('admin.sites.toggle', $site))
            ->assertSessionHasNoErrors();

        $this->assertFalse($site->fresh()->active);

        $this->actingAs($this->admin)
            ->patch(route('admin.sites.toggle', $site))
            ->assertSessionHasNoErrors();

        $this->assertTrue($site->fresh()->active);
    }

    public function test_jefe_de_area_cannot_manage_sites(): void
    {
        $site = Site::create(['name' => 'Reportes', 'url' => 'https://reportes.grupoavitare.com/']);

        $this->actingAs($this->jefe)
            ->get(route('admin.sites.index'))
            ->assertForbidden();

        $this->actingAs($this->jefe)
            ->post(route('admin.sites.store'), ['name' => 'Otro', 'url' => 'https://otro.test'])
            ->assertForbidden();

        $this->actingAs($this->jefe)
            ->patch(route('admin.sites.toggle', $site))
            ->assertForbidden();
    }

    public function test_collaborators_only_see_active_sites(): void
    {
        Site::create(['name' => 'Habilitado', 'url' => 'https://habilitado.test', 'active' => true]);
        Site::create(['name' => 'Deshabilitado', 'url' => 'https://deshabilitado.test', 'active' => false]);

        $response = $this->actingAs($this->jefe)->get(route('sites.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('sites', fn ($sites) => count($sites) === 1 && $sites[0]['name'] === 'Habilitado'));
    }
}
