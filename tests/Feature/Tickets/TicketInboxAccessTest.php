<?php

namespace Tests\Feature\Tickets;

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

class TicketInboxAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $ti;

    private User $jefe;

    private Ticket $ticket;

    private Category $hardware;

    private Category $redes;

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

        $this->hardware = Category::create(['name' => 'Hardware', 'icon' => '💻']);
        $this->redes = Category::create(['name' => 'Redes', 'icon' => '🌐']);
        $type = TicketType::create(['name' => 'Incidente']);
        $priority = Priority::create(['name' => 'Media', 'color' => '#CA8A04', 'sla_response_minutes' => 120, 'sla_resolution_minutes' => 1440, 'rank' => 2]);

        $this->ticket = Ticket::create([
            'user_id' => $this->jefe->id,
            'category_id' => $this->hardware->id,
            'type_id' => $type->id,
            'priority_id' => $priority->id,
            'code' => 'TCK-0001',
            'subject' => 'Se rompió el mouse',
            'description' => 'El mouse no enciende.',
            'status' => 'EN_PROCESO',
        ]);

        Ticket::create([
            'user_id' => $this->jefe->id,
            'category_id' => $this->redes->id,
            'type_id' => $type->id,
            'priority_id' => $priority->id,
            'code' => 'TCK-0002',
            'subject' => 'Sin internet en la oficina',
            'description' => 'No hay conexión desde la mañana.',
            'status' => 'EN_PROCESO',
        ]);
    }

    public function test_admin_can_view_the_ticket_inbox(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tickets.inbox'))
            ->assertOk();
    }

    public function test_ti_can_view_the_ticket_inbox(): void
    {
        $this->actingAs($this->ti)
            ->get(route('tickets.inbox'))
            ->assertOk();
    }

    public function test_jefe_de_area_cannot_view_the_ticket_inbox(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('tickets.inbox'))
            ->assertForbidden();
    }

    public function test_ti_can_resolve_a_ticket(): void
    {
        $this->actingAs($this->ti)
            ->post(route('tickets.resolve', $this->ticket), [
                'solution' => 'Se reemplazó el mouse.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($this->ticket->fresh()->resolved_at);
    }

    public function test_jefe_de_area_cannot_resolve_a_ticket(): void
    {
        $this->actingAs($this->jefe)
            ->post(route('tickets.resolve', $this->ticket), [
                'solution' => 'Se reemplazó el mouse.',
            ])
            ->assertForbidden();
    }

    public function test_admin_visiting_mis_tickets_is_redirected_to_the_inbox(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tickets.index'))
            ->assertRedirect(route('tickets.inbox'));
    }

    public function test_ti_visiting_mis_tickets_is_redirected_to_the_inbox(): void
    {
        $this->actingAs($this->ti)
            ->get(route('tickets.index'))
            ->assertRedirect(route('tickets.inbox'));
    }

    public function test_jefe_de_area_visiting_mis_tickets_sees_the_collaborator_view(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('tickets.index'))
            ->assertOk();
    }

    public function test_inbox_can_be_filtered_by_category(): void
    {
        $response = $this->actingAs($this->ti)
            ->get(route('tickets.inbox', ['category' => $this->redes->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('tickets', fn ($tickets) => count($tickets) === 1 && $tickets[0]['code'] === 'TCK-0002'));
    }

    public function test_inbox_can_be_searched_by_code(): void
    {
        $response = $this->actingAs($this->ti)
            ->get(route('tickets.inbox', ['search' => 'TCK-0001']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('tickets', fn ($tickets) => count($tickets) === 1 && $tickets[0]['code'] === 'TCK-0001'));
    }
}
