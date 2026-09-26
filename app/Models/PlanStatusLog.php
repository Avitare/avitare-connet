<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanStatusLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'monthly_plan_id',
        'from_status',
        'to_status',
        'performed_by',
        'reason',
    ];

    public function monthlyPlan(): BelongsTo
    {
        return $this->belongsTo(MonthlyPlan::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
