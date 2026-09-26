<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequerimientoBudgetItem extends Model
{
    protected $fillable = [
        'requerimiento_id',
        'objetivo',
        'monto_solicitado',
        'fecha_requerida',
        'especificacion_uso',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'monto_solicitado' => 'decimal:2',
            'fecha_requerida' => 'date',
        ];
    }

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(Requerimiento::class);
    }
}
