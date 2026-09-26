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

        $this->logEvent($ticket, $actor, 'status_changed', $note ?? "Estado cambiado de {$current} a {$newStatus}", [
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

    public function resolve(Ticket $ticket, User $actor, string $solution): Ticket
    {
        $ticket = $this->changeStatus($ticket, 'RESUELTO', $actor, $solution);

        $this->logEvent($ticket, $actor, 'resolved', $solution);

        return $ticket;
    }

    public function confirm(Ticket $ticket, User $actor): Ticket
    {
        $ticket = $this->changeStatus($ticket, 'CERRADO', $actor, 'Usuario confirmó la solución');

        $this->logEvent($ticket, $actor, 'confirmed', 'Usuario confirmó la solución');

        return $ticket;
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
