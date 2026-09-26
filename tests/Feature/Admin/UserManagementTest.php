<?php

namespace Tests\Feature\Admin;

use App\Models\Area;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_area');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $company = Company::create(['name' => 'Empresa']);
        $this->area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);
    }

    public function test_super_admin_can_create_a_user_with_a_role(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Nuevo Jefe',
                'email' => 'nuevo.jefe@ciclomensual.test',
                'password' => 'password123',
                'area_id' => $this->area->id,
                'active' => true,
                'role' => 'jefe_area',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'nuevo.jefe@ciclomensual.test')->firstOrFail();
        $this->assertTrue($user->hasRole('jefe_area'));
        $this->assertEquals($this->area->id, $user->area_id);
    }

    public function test_super_admin_can_edit_a_user_and_change_their_role(): void
    {
        $user = User::factory()->create(['area_id' => $this->area->id]);
        $user->assignRole('jefe_area');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Nombre Actualizado',
                'email' => $user->email,
                'area_id' => $this->area->id,
                'active' => true,
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertEquals('Nombre Actualizado', $user->name);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('jefe_area'));
    }

    public function test_super_admin_can_delete_a_user(): void
    {
        $user = User::factory()->create(['area_id' => $this->area->id]);
        $user->assignRole('jefe_area');

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $jefe = User::factory()->create(['area_id' => $this->area->id]);
        $jefe->assignRole('jefe_area');
        $target = User::factory()->create(['area_id' => $this->area->id]);

        $this->actingAs($jefe)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($jefe)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();
    }
}
