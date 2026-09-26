<?php

namespace App\Models;

use App\Enums\ActivityProgressType;
use App\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Activity extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Activity $activity) {
            if (is_null($activity->added_after_approval)) {
                $activity->added_after_approval = false;
            }

            if ($activity->added_after_approval) {
                return;
            }

            if ($activity->planGroup?->monthlyPlan?->status === PlanStatus::Vigente) {
                $activity->added_after_approval = true;
            }
        });
    }

    protected $fillable = [
        'plan_group_id',
        'responsible_name',
        'name',
        'progress_type',
        'numeric_goal_target',
        'weight',
        'budget',
        'deliverable',
        'deliverable_type',
        'deliverable_path',
        'deliverable_original_name',
        'deliverable_mime_type',
        'deliverable_size',
        'deliverable_url',
        'notes',
        'carried_over',
        'carried_over_from_id',
        'added_after_approval',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'progress_type' => ActivityProgressType::class,
            'numeric_goal_target' => 'decimal:2',
            'weight' => 'decimal:2',
            'budget' => 'decimal:2',
            'carried_over' => 'boolean',
            'added_after_approval' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function planGroup(): BelongsTo
    {
        return $this->belongsTo(PlanGroup::class);
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(ActivityWeek::class)->orderBy('week_number');
    }

    /**
     * @return array<int, int>
     */
    public function plannedWeekNumbers(): array
    {
        return $this->weeks->pluck('week_number')->sort()->values()->all();
    }

    public function minPlannedWeek(): ?int
    {
        return $this->weeks->min('week_number');
    }

    public function maxPlannedWeek(): ?int
    {
        return $this->weeks->max('week_number');
    }

    public function carriedOverFrom(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'carried_over_from_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(ActivityProgressReport::class)->orderBy('created_at');
    }

    public function latestProgressReport(): HasOne
    {
        return $this->hasOne(ActivityProgressReport::class)->latestOfMany();
    }

    public function target(): float
    {
        return $this->progress_type === ActivityProgressType::MetaNumerica
            ? (float) $this->numeric_goal_target
            : 100.0;
    }

    /**
     * Actividades congeladas en vigencia, las únicas que entran al cumplimiento oficial.
     * Las agregadas después de aprobar el plan quedan fuera (ver memoria de decisiones de negocio).
     */
    public function scopeOfficial(Builder $query): Builder
    {
        return $query->where('added_after_approval', false);
    }
}
