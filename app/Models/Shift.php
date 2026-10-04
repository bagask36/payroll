<?php

namespace App\Models;

use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'start_time',
        'end_time',
        'is_overnight',
        'late_tolerance_minutes',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_overnight' => 'boolean',
            'late_tolerance_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Daily schedule assignments assigned to this shift.
     *
     * @return HasMany<ScheduleAssignment, $this>
     */
    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }

    /**
     * Calculate scheduled work duration in hours (accounting for overnight midnight wrap).
     */
    public function getDurationHours(): float
    {
        $start = Carbon::createFromTimeString($this->start_time);
        $end = Carbon::createFromTimeString($this->end_time);

        if ($this->is_overnight || $end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return round($start->floatDiffInHours($end), 2);
    }

    /**
     * Check if a given check-in time exceeds the start time plus late tolerance.
     */
    public function isLate(string $checkInTime): bool
    {
        $start = Carbon::createFromTimeString($this->start_time);
        $checkIn = Carbon::createFromTimeString($checkInTime);
        $toleranceLimit = $start->copy()->addMinutes($this->late_tolerance_minutes);

        return $checkIn->greaterThan($toleranceLimit);
    }

    /**
     * Filter query to active shifts only.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Check if this is an operational rotating shift.
     */
    public function isShift(): bool
    {
        return ($this->type ?? 'shift') === 'shift';
    }

    /**
     * Check if this is a regular non-shift office hour schedule.
     */
    public function isNonShift(): bool
    {
        return $this->type === 'non_shift';
    }

    /**
     * Scope query to rotating shifts only.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeShift(Builder $query): void
    {
        $query->where('type', 'shift');
    }

    /**
     * Scope query to non-shift regular schedules only.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeNonShift(Builder $query): void
    {
        $query->where('type', 'non_shift');
    }
}
