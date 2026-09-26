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

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $ti;

    private User $otherTi;

    private User $owner;

    private Priority $baja;

    private Priority $alta;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('ti');
        Role::findOrCreate('jefe_area');

        $this->ti = User::factory()->create();
        $this->ti->assignRole('ti');

        $this->otherTi = User::factory()->create();
        $this->otherTi->assignRole('ti');

        $company = Company::create(['name' => 'Empresa']);
        $area = Area::create(['company_id' => $company->id, 'name' => 'Operaciones', 'slug' => 'operaciones', 'active' => true]);

        $this->owner = User::factory()->create(['area_id' => $area->id]);
        $this->owner->assignRole('jefe_area');

        $this->baja = Priority::create(['name' => 'Baja', 'color' => '#16A34A', 'sla_response_minutes' => 480, 'sla_resolution_minutes' => 4320, 'rank' => 1]);
        $this->alta = Priority::create(['name' => 'Alta', 'color' => '#EA580C', 'sla_response_minutes' => 30, 'sla_resolution_minutes' => 480, 'rank' => 3]);
    }

    private function makeTicket(Priority $priority, string $status = 'NUEVO'): Ticket
    {
        $category = Category::create(['name' => 'Hardware', 'icon' => '💻']);
        $type = TicketType::create(['name' => 'Incidente']);

        return Ticket::create([
            'user_id' => $this->owner->id,
            'category_id' => $category->id,
            'type_id' => $type->id,
            'priority_id' => $priority->id,
            'code' => 'TI-'.uniqid(),
            'subject' => 'Se rompió el mouse',
            'description' => 'El mouse no enciende.',
            'status' => $status,
            'sla_response_due_at' => now()->addMinutes($priority->sla_response_minutes),
            'sla_resolution_due_at' => now()->addMinutes($priority->sla_resolution_minutes),
        ]);
    }

    public function test_ti_can_open_a_ticket_it_does_not_own(): void
    {
        $ticket = $this->makeTicket($this->baja);

        $this->actingAs($this->ti)
            ->get(route('tickets.show', $ticket))
            ->assertOk();
    }

    public function test_ti_can_download_an_attachment_on_a_ticket_it_does_not_own(): void
    {
        $ticket = $this->makeTicket($this->baja);
        $ticket->attachments()->create([
            'uploaded_by' => $this->owner->id,
            'path' => 'tickets/1/foo.txt',
            'original_name' => 'foo.txt',
            'mime' => 'text/plain',
            'size' => 10,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put('tickets/1/foo.txt', 'contenido');

        $attachment = $ticket->attachments()->first();

        $this->actingAs($this->ti)
            ->get(route('tickets.attachments.download', $attachment))
            ->assertOk();
    }

    public function test_resolving_a_brand_new_ticket_bridges_through_en_proceso(): void
    {
        $ticket = $this->makeTicket($this->baja, 'NUEVO');

        $this->actingAs($this->ti)
            ->post(route('tickets.resolve', $ticket), ['solution' => 'Se reemplazó el mouse.'])
            ->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame('RESUELTO', $ticket->status);
        $this->assertNotNull($ticket->first_response_at);
        $this->assertNotNull($ticket->resolved_at);

        // Un solo evento "resolved", no un status_changed + resolved duplicados.
        $this->assertSame(1, $ticket->events()->where('type', 'resolved')->count());
    }

    public function test_confirming_a_resolved_ticket_logs_a_single_event(): void
    {
        $ticket = $this->makeTicket($this->baja, 'RESUELTO');
        $ticket->update(['resolved_at' => now()]);

        $this->actingAs($this->owner)
            ->post(route('tickets.confirm', $ticket))
            ->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame('CERRADO', $ticket->status);
        $this->assertSame(1, $ticket->events()->where('type', 'confirmed')->count());
    }

    public function test_owner_can_cancel_their_own_open_ticket(): void
    {
        $ticket = $this->makeTicket($this->baja, 'NUEVO');

        $this->actingAs($this->owner)
            ->post(route('tickets.cancel', $ticket), ['reason' => 'Ya no hace falta.'])
            ->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame('CANCELADO', $ticket->status);
        $this->assertSame(1, $ticket->events()->where('type', 'cancelled')->count());
    }

    public function test_owner_cannot_cancel_an_already_resolved_ticket(): void
    {
        $ticket = $this->makeTicket($this->baja, 'RESUELTO');

        $this->actingAs($this->owner)
            ->post(route('tickets.cancel', $ticket), [])
            ->assertForbidden();

        $this->assertSame('RESUELTO', $ticket->fresh()->status);
    }

    public function test_someone_else_cannot_cancel_another_users_ticket(): void
    {
        $ticket = $this->makeTicket($this->baja, 'NUEVO');
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post(route('tickets.cancel', $ticket), [])
            ->assertForbidden();
    }

    public function test_ti_can_assign_a_ticket_to_themselves(): void
    {
        $ticket = $this->makeTicket($this->baja);

        $this->actingAs($this->ti)
            ->post(route('tickets.assign', $ticket))
            ->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame($this->ti->id, $ticket->assigned_to);
        $this->assertNotNull($ticket->assigned_at);
        $this->assertSame(1, $ticket->events()->where('type', 'assigned')->count());
    }

    public function test_a_ticket_can_be_reassigned_to_a_different_ti_agent(): void
    {
        $ticket = $this->makeTicket($this->baja);
        $ticket->update(['assigned_to' => $this->ti->id, 'assigned_at' => now()]);

        $this->actingAs($this->otherTi)
            ->post(route('tickets.assign', $ticket))
            ->assertSessionHasNoErrors();

        $this->assertSame($this->otherTi->id, $ticket->fresh()->assigned_to);
    }

    public function test_owner_cannot_assign_a_ticket(): void
    {
        $ticket = $this->makeTicket($this->baja);

        $this->actingAs($this->owner)
            ->post(route('tickets.assign', $ticket))
            ->assertForbidden();
    }

    public function test_ti_can_reprioritize_a_ticket_and_sla_is_recalculated(): void
    {
        $ticket = $this->makeTicket($this->baja);

        $this->actingAs($this->ti)
            ->post(route('tickets.reprioritize', $ticket), ['priority_id' => $this->alta->id])
            ->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame($this->alta->id, $ticket->priority_id);
        $this->assertSame(1, $ticket->events()->where('type', 'reprioritized')->count());

        $expectedResolutionDueAt = $ticket->created_at->clone()->addMinutes($this->alta->sla_resolution_minutes);
        $this->assertTrue($ticket->sla_resolution_due_at->equalTo($expectedResolutionDueAt));
    }

    public function test_owner_cannot_reprioritize_their_own_ticket(): void
    {
        $ticket = $this->makeTicket($this->baja);

        $this->actingAs($this->owner)
            ->post(route('tickets.reprioritize', $ticket), ['priority_id' => $this->alta->id])
            ->assertForbidden();
    }

    public function test_rating_a_ticket_that_is_not_closed_is_rejected(): void
    {
        $ticket = $this->makeTicket($this->baja, 'RESUELTO');

        $this->actingAs($this->owner)
            ->post(route('tickets.rate', $ticket), ['rating' => 5])
            ->assertSessionHasErrors('ticket');

        $this->assertNull($ticket->fresh()->satisfaction_rating);
    }

    public function test_rating_an_already_rated_ticket_is_rejected(): void
    {
        $ticket = $this->makeTicket($this->baja, 'CERRADO');
        $ticket->update(['satisfaction_rating' => 4]);

        $this->actingAs($this->owner)
            ->post(route('tickets.rate', $ticket), ['rating' => 1])
            ->assertSessionHasErrors('ticket');

        $this->assertSame(4, $ticket->fresh()->satisfaction_rating);
    }

    public function test_ticket_alerts_count_for_ti_only_counts_unassigned_or_own_open_tickets(): void
    {
        $this->makeTicket($this->baja, 'NUEVO');
        $mine = $this->makeTicket($this->baja, 'EN_PROCESO');
        $mine->update(['assigned_to' => $this->ti->id]);
        $someoneElses = $this->makeTicket($this->baja, 'EN_PROCESO');
        $someoneElses->update(['assigned_to' => $this->otherTi->id]);

        $response = $this->actingAs($this->ti)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page->where('ticketAlerts', 2));
    }

    public function test_ticket_alerts_count_for_owner_counts_own_resolved_tickets(): void
    {
        $this->makeTicket($this->baja, 'RESUELTO');
        $this->makeTicket($this->baja, 'NUEVO');

        $response = $this->actingAs($this->owner)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page->where('ticketAlerts', 1));
    }
}
