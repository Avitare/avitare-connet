<?php

namespace App\Models;

use App\Enums\RequerimientoStatus;
use App\Enums\RequerimientoType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requerimiento extends Model
{
    /**
     * Foto fija del documento controlado vigente al momento de crear un
     * requerimiento de marketing (Código/Versión/Fecha de aprobación del
     * formato oficial de la empresa) — se estampa en el registro, no se lee
     * en vivo, para que un requerimiento viejo siga mostrando bajo qué
     * versión del formato se armó aunque el formato cambie después.
     */
    public const MARKETING_FORMAT = [
        'code' => 'AVI-GG-PMK-001',
        'version' => 'V1.0',
        'approved_at' => '2025-06-06',
    ];

    public const SERVICIO_FORMAT = [
        'code' => 'AVI-LGS-PRS-001',
        'version' => 'V2.0',
        'approved_at' => '2025-04-12',
    ];

    public const PRESUPUESTO_FORMAT = [
        'code' => 'AVI-GG-PAR-001',
        'version' => 'V1.0',
        'approved_at' => '2025-08-05',
    ];

    public const TI_FORMAT = [
        'code' => 'AVI-TI-PSR-001',
        'version' => 'V1.1',
        'approved_at' => '2025-06-12',
    ];

    protected $fillable = [
        'area_id',
        'user_id',
        'activity_id',
        'type',
        'status',
        'routed_to',
        'detail',
        'especificaciones',
        'requested_amount',
        'approved_amount',
        'needed_by',
        'format_code',
        'format_version',
        'format_approved_at',
        'submitted_at',
        'decided_at',
        'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => RequerimientoType::class,
            'status' => RequerimientoStatus::class,
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'needed_by' => 'date',
            'format_approved_at' => 'date',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(RequerimientoStatusLog::class)->orderBy('created_at');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(RequerimientoMaterial::class)->orderBy('position');
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(RequerimientoBudgetItem::class)->orderBy('position');
    }
}
