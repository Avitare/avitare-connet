<?php

namespace Tests\Feature\Admin;

use App\Models\Area;
use App\Models\Category;
use App\Models\Company;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $ti;

    private User $jefe;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('ti');
        Role::findOrCreate('jefe_area');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->ti = User::factory()->create();
        $this->ti->assignRole('ti');

        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);

        $this->jefe = User::factory()->create(['area_id' => $area->id]);
        $this->jefe->assignRole('jefe_area');
    }

    public function test_admin_can_create_a_category(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Impresoras',
                'icon' => '🖨️',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Impresoras']);
    }

    public function test_ti_can_create_edit_and_delete_a_category(): void
    {
        $this->actingAs($this->ti)
            ->post(route('admin.categories.store'), [
                'name' => 'Impresoras',
                'icon' => '🖨️',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::where('name', 'Impresoras')->firstOrFail();

        $this->actingAs($this->ti)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Impresoras y escáneres',
                'icon' => '🖨️',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Impresoras y escáneres']);

        $this->actingAs($this->ti)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_jefe_de_area_cannot_manage_categories(): void
    {
        $category = Category::create(['name' => 'Hardware', 'icon' => '💻']);

        $this->actingAs($this->jefe)
            ->get(route('admin.categories.index'))
            ->assertForbidden();

        $this->actingAs($this->jefe)
            ->post(route('admin.categories.store'), ['name' => 'Otra', 'icon' => null])
            ->assertForbidden();

        $this->actingAs($this->jefe)
            ->delete(route('admin.categories.destroy', $category))
            ->assertForbidden();
    }

    public function test_a_category_with_tickets_cannot_be_deleted(): void
    {
        $category = Category::create(['name' => 'Hardware', 'icon' => '💻']);
        $type = TicketType::create(['name' => 'Incidente']);
        $priority = Priority::create(['name' => 'Media', 'color' => '#CA8A04', 'sla_response_minutes' => 120, 'sla_resolution_minutes' => 1440, 'rank' => 2]);

        Ticket::create([
            'user_id' => $this->jefe->id,
            'category_id' => $category->id,
            'type_id' => $type->id,
            'priority_id' => $priority->id,
            'code' => 'TCK-0001',
            'subject' => 'Se rompió el mouse',
            'description' => 'El mouse no enciende.',
            'status' => 'NUEVO',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
