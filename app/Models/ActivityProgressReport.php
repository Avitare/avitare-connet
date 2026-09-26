<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityProgressReport extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'activity_id',
        'value',
        'reported_by',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ActivityProgressReportAttachment::class);
    }
}
