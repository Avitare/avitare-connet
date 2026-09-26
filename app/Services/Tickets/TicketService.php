<?php

namespace App\Services\Tickets;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    /**
     * Valid status transitions: from => [allowed next states].
     */
    private const TRANSITIONS = [
        'NUEVO' => ['EN_PROCESO', 'CANCELADO'],
        'ASIGNADO' => ['EN_PROCESO', 'CANCELADO'],
        'EN_PROCESO' => ['ESPERANDO_USUARIO', 'RESUELTO', 'CANCELADO'],
        'ESPERANDO_USUARIO' => ['EN_PROCESO', 'CANCELADO'],
        'RESUELTO' => ['CERRADO', 'EN_PROCESO'],
        'CERRADO' => [],
        'CANCELADO' => [],
    ];

    /**
     * Tipo de evento a registrar según el estado destino, para que la
     * línea de tiempo muestre un icono acorde (ver TICKET_EVENT_ICON en
     * ticketDisplay.tsx) sin tener que loguear un evento duplicado desde
     * cada método que llama a changeStatus().
     */
    private const STATUS_EVENT_TYPES = [
        'RESUELTO' => 'resolved',
        'CERRADO' => 'confirmed',
        'CANCELADO' => 'cancelled',
    ];

    public function create(array $data, User $creator): Ticket
    {
        return DB::transaction(function () use ($data, $creator) {
            $priority = Priority::findOrFail($data['priority_id']);
            $category = Category::findOrFail($data['category_id']);
            $now = Carbon::now();

            $ticket = Ticket::create([
                'code' => $this->generateCode(),
                'user_id' => $creator->id,
                'area_id' => $creator->area_id,
                'category_id' => $category->id,
                'type_id' => $data['type_id'],
                'priority_id' => $priority->id,
                'status' => 'NUEVO',
                'subject' => $data['subject'],
                'description' => $data['description'],
                'sla_response_due_at' => $now->clone()->addMinutes($priority->sla_response_minutes),
                'sla_resolution_due_at' => $now->clone()->addMinutes($priority->sla_resolution_minutes),
            ]);

            $this->logEvent($ticket, $creator, 'created', "Ticket creado: {$ticket->subject}");

            return $ticket->fresh();
        });
    }

    public function changeStatus(Ticket $ticket, string $newStatus, User $actor, ?string $note = null): Ticket
    {
        $current = $ticket->status;

        if (! in_array($newStatus, self::TRANSITIONS[$current] ?? [], true)) {
            throw new DomainException("No se puede pasar de {$current} a {$newStatus}.");
        }

        $attributes = ['status' => $newStatus];

        if ($newStatus === 'EN_PROCESO' && ! $ticket->first_response_at) {
            $attributes['first_response_at'] = Carbon::now();
        }

        if ($newStatus === 'RESUELTO') {
            $attributes['resolved_at'] = Carbon::now();
        }

        if ($newStatus === 'CERRADO') {
            $attributes['closed_at'] = Carbon::now();
        }

        $ticket->update($attributes);

        $eventType = self::STATUS_EVENT_TYPES[$newStatus] ?? 'status_changed';

        $this->logEvent($ticket, $actor, $eventType, $note ?? "Estado cambiado de {$current} a {$newStatus}", [
            'from' => $current,
            'to' => $newStatus,
        ]);

        return $ticket->fresh();
    }

    public function addComment(Ticket $ticket, User $actor, string $body): TicketEvent
    {
        return $this->logEvent($ticket, $actor, 'comment', $body);
    }

    public function addAttachment(Ticket $ticket, User $actor, UploadedFile $file): TicketEvent
    {
        $path = Storage::disk('local')->putFile("tickets/{$ticket->id}", $file);

        $ticket->attachments()->create([
            'uploaded_by' => $actor->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return $this->logEvent($ticket, $actor, 'attachment_added', $file->getClientOriginalName());
    }

    /**
     * Un ticket recién creado (o ya asignado pero sin arrancar) puede
     * resolverse en un solo paso: se pasa primero por "en proceso" para que
     * quede registrado el primer contacto (first_response_at) antes de
     * marcarlo resuelto, en vez de exigirle al agente dos clics separados.
     */
    public function resolve(Ticket $ticket, User $actor, string $solution): Ticket
    {
        if (in_array($ticket->status, ['NUEVO', 'ASIGNADO'], true)) {
            $ticket = $this->changeStatus($ticket, 'EN_PROCESO', $actor);
        }

        return $this->changeStatus($ticket, 'RESUELTO', $actor, $solution);
    }

    public function confirm(Ticket $ticket, User $actor): Ticket
    {
        return $this->changeStatus($ticket, 'CERRADO', $actor, 'Usuario confirmó la solución');
    }

    public function cancel(Ticket $ticket, User $actor, ?string $reason = null): Ticket
    {
        return $this->changeStatus($ticket, 'CANCELADO', $actor, $reason);
    }

    public function assign(Ticket $ticket, User $agent): Ticket
    {
        $ticket->update([
            'assigned_to' => $agent->id,
            'assigned_at' => Carbon::now(),
        ]);

        $this->logEvent($ticket, $agent, 'assigned', "{$agent->name} se asignó el ticket.");

        return $ticket->fresh();
    }

    public function reprioritize(Ticket $ticket, Priority $priority, User $actor): Ticket
    {
        $previous = $ticket->priority;

        $ticket->update([
            'priority_id' => $priority->id,
            'sla_response_due_at' => $ticket->created_at->clone()->addMinutes($priority->sla_response_minutes),
            'sla_resolution_due_at' => $ticket->created_at->clone()->addMinutes($priority->sla_resolution_minutes),
        ]);

        $this->logEvent(
            $ticket,
            $actor,
            'reprioritized',
            "Prioridad cambiada de {$previous->name} a {$priority->name}.",
        );

        return $ticket->fresh();
    }

    public function rate(Ticket $ticket, int $rating, ?string $comment): Ticket
    {
        $ticket->update([
            'satisfaction_rating' => $rating,
            'satisfaction_comment' => $comment,
        ]);

        return $ticket->fresh();
    }

    private function logEvent(Ticket $ticket, ?User $actor, string $type, ?string $body = null, array $meta = []): TicketEvent
    {
        return $ticket->events()->create([
            'user_id' => $actor?->id,
            'type' => $type,
            'body' => $body,
            'meta' => $meta ?: null,
        ]);
    }

    private function generateCode(): string
    {
        $year = (int) Carbon::now()->format('Y');

        $sequence = DB::table('ticket_sequences')->where('year', $year)->lockForUpdate()->first();

        if (! $sequence) {
            DB::table('ticket_sequences')->insert(['year' => $year, 'last_number' => 0]);
        }

        $next = DB::table('ticket_sequences')->where('year', $year)->lockForUpdate()->value('last_number') + 1;

        DB::table('ticket_sequences')->where('year', $year)->update(['last_number' => $next]);

        return sprintf('TI-%d-%05d', $year, $next);
    }
}
