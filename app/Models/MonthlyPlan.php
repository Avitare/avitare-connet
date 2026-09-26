<?php

namespace App\Models;

use App\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyPlan extends Model
{
    protected $fillable = [
        'period_id',
        'area_id',
        'status',
        'cloned_from_plan_id',
        'approved_by',
        'approved_at',
        'frozen_at',
        'final_compliance_plan_aprobado',
        'final_compliance_total_mes',
        'final_effectiveness',
        'final_punctuality',
        'closed_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlanStatus::class,
            'approved_at' => 'datetime',
            'frozen_at' => 'datetime',
            'closed_at' => 'datetime',
            'final_compliance_plan_aprobado' => 'decimal:2',
            'final_compliance_total_mes' => 'decimal:2',
            'final_effectiveness' => 'decimal:2',
            'final_punctuality' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function clonedFrom(): BelongsTo
    {
        return $this->belongsTo(MonthlyPlan::class, 'cloned_from_plan_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PlanStatusLog::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(PlanGroup::class)->orderBy('position');
    }
}
