<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUSES = [
        'NUEVO',
        'ASIGNADO',
        'EN_PROCESO',
        'ESPERANDO_USUARIO',
        'RESUELTO',
        'CERRADO',
        'CANCELADO',
    ];

    public const OPEN_STATUSES = ['NUEVO', 'ASIGNADO', 'EN_PROCESO', 'ESPERANDO_USUARIO'];

    protected $fillable = [
        'code',
        'user_id',
        'area_id',
        'category_id',
        'type_id',
        'priority_id',
        'assigned_to',
        'assigned_at',
        'status',
        'subject',
        'description',
        'sla_response_due_at',
        'sla_resolution_due_at',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'satisfaction_rating',
        'satisfaction_comment',
    ];

    protected function casts(): array
    {
        return [
            'sla_response_due_at' => 'datetime',
            'sla_resolution_due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'assigned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'type_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    protected function responseBreached(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->sla_response_due_at) {
                return false;
            }

            $reference = $this->first_response_at ?? $this->openReferenceMoment();

            return $reference !== null && $reference->greaterThan($this->sla_response_due_at);
        });
    }

    protected function resolutionBreached(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->sla_resolution_due_at) {
                return false;
            }

            $reference = $this->resolved_at ?? $this->openReferenceMoment();

            return $reference !== null && $reference->greaterThan($this->sla_resolution_due_at);
        });
    }

    protected function resolutionMinutesRemaining(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->sla_resolution_due_at) {
                return null;
            }

            $reference = $this->resolved_at ?? $this->openReferenceMoment();

            if ($reference === null) {
                return null;
            }

            return (int) $reference->diffInMinutes($this->sla_resolution_due_at, false);
        });
    }

    /**
     * "Ahora" como referencia solo tiene sentido mientras el ticket sigue
     * abierto: uno cancelado (sin resolved_at) no debería seguir mostrando
     * un SLA corriendo en vivo — quedaría marcado como incumplido para
     * siempre a medida que pasa el tiempo, lo cual no aporta nada.
     */
    private function openReferenceMoment(): ?Carbon
    {
        return $this->status === 'CANCELADO' ? null : Carbon::now();
    }
}
