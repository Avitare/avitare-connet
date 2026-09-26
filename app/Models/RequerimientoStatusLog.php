<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequerimientoStatusLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'requerimiento_id',
        'from_status',
        'to_status',
        'performed_by',
        'comment',
    ];

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(Requerimiento::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
