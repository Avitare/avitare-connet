<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'require_deliverable_to_close_activity',
        'require_weeks_completed_to_mark_activity_done',
        'activity_risk_threshold',
    ];

    protected function casts(): array
    {
        return [
            'require_deliverable_to_close_activity' => 'boolean',
            'require_weeks_completed_to_mark_activity_done' => 'boolean',
            'activity_risk_threshold' => 'integer',
        ];
    }

    /**
     * Única fila de configuración global del sistema; se crea con estos
     * valores por defecto la primera vez que se pide. Van explícitos aquí
     * (no solo en la migración) porque Eloquent no relee los defaults de
     * columna después de un create() sin atributos: el modelo en memoria
     * quedaría con null en vez del valor real de la fila recién insertada.
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'require_deliverable_to_close_activity' => true,
            'require_weeks_completed_to_mark_activity_done' => true,
            'activity_risk_threshold' => 80,
        ]);
    }
}
