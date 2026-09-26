<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequerimientoMaterial extends Model
{
    protected $fillable = [
        'requerimiento_id',
        'material',
        'especificaciones',
        'publico_objetivo',
        'image_path',
        'image_original_name',
        'position',
    ];

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(Requerimiento::class);
    }
}
