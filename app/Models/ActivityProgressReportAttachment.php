<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityProgressReportAttachment extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'activity_progress_report_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function progressReport(): BelongsTo
    {
        return $this->belongsTo(ActivityProgressReport::class, 'activity_progress_report_id');
    }
}
