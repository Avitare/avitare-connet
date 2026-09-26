<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanGroup extends Model
{
    protected $fillable = [
        'monthly_plan_id',
        'name',
        'position',
    ];

    public function monthlyPlan(): BelongsTo
    {
        return $this->belongsTo(MonthlyPlan::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderBy('id');
    }
}
