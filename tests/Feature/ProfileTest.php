<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_area');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_profile_page_is_displayed_for_admin(): void
    {
        $response = $this
            ->actingAs($this->admin())
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_is_forbidden_for_non_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('jefe_area');

        $this->actingAs($user)
            ->get('/profile')
            ->assertForbidden();
    }

    public function test_admin_can_update_their_profile_information(): void
    {
        $admin = $this->admin();

        $response = $this
            ->actingAs($admin)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $admin->refresh();

        $this->assertSame('Test User', $admin->name);
        $this->assertSame('test@example.com', $admin->email);
        $this->assertNull($admin->email_verified_at);
    }

    public function test_non_admin_cannot_update_their_profile_information(): void
    {
        $user = User::factory()->create();
        $user->assignRole('jefe_area');

        $this->actingAs($user)
            ->patch('/profile', ['name' => 'Otro nombre', 'email' => $user->email])
            ->assertForbidden();

        $this->assertNotSame('Otro nombre', $user->fresh()->name);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $admin = $this->admin();

        $response = $this
            ->actingAs($admin)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $admin->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($admin->refresh()->email_verified_at);
    }

    public function test_account_deletion_via_profile_is_always_blocked(): void
    {
        // La baja de cuentas es exclusiva de Admin > Usuarios; nadie, ni
        // siquiera el propio admin, puede darse de baja desde /profile.
        $this->actingAs($this->admin())
            ->delete('/profile', ['password' => 'password'])
            ->assertForbidden();
    }
}
