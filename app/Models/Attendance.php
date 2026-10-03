<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'schedule_assignment_id',
        'employee_id',
        'doctor_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'work_duration_minutes',
        'late_duration_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'work_duration_minutes' => 'integer',
            'late_duration_minutes' => 'integer',
        ];
    }

    /**
     * Scheduled duty roster link.
     *
     * @return BelongsTo<ScheduleAssignment, $this>
     */
    public function scheduleAssignment(): BelongsTo
    {
        return $this->belongsTo(ScheduleAssignment::class);
    }

    /**
     * Associated employee (if employee).
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Associated doctor (if doctor).
     *
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Raw biometric, GPS, and timestamp evidence events.
     *
     * @return HasMany<AttendanceEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(AttendanceEvent::class);
    }

    /**
     * Manual adjustment requests and approvals.
     *
     * @return HasMany<AttendanceAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(AttendanceAdjustment::class);
    }

    /**
     * Record a raw clock-in event and update attendance status.
     *
     * @param  array<string, mixed>  $evidence
     */
    public function recordClockIn(CarbonInterface|string $time, array $evidence = []): AttendanceEvent
    {
        $clockIn = $time instanceof CarbonInterface ? $time : Carbon::parse($time);
        $this->clock_in = $clockIn;

        // Check lateness against scheduled shift if available
        if ($this->scheduleAssignment && $this->scheduleAssignment->shift) {
            $shift = $this->scheduleAssignment->shift;
            if ($shift->isLate($clockIn->toTimeString())) {
                $this->status = 'late';
                $shiftStart = Carbon::parse($this->date->toDateString().' '.$shift->start_time);
                $this->late_duration_minutes = (int) max(0, $shiftStart->diffInMinutes($clockIn));
            } else {
                $this->status = 'present';
                $this->late_duration_minutes = 0;
            }
        } else {
            $this->status = 'present';
        }

        $this->save();

        return $this->events()->create(array_merge([
            'event_type' => 'clock_in',
            'recorded_at' => $clockIn,
            'latitude' => $evidence['latitude'] ?? 0.0,
            'longitude' => $evidence['longitude'] ?? 0.0,
            'distance_meters' => $evidence['distance_meters'] ?? 0,
            'is_within_geofence' => $evidence['is_within_geofence'] ?? true,
            'device_info' => $evidence['device_info'] ?? null,
            'photo_path' => $evidence['photo_path'] ?? null,
            'face_matched' => $evidence['face_matched'] ?? false,
            'face_similarity' => $evidence['face_similarity'] ?? null,
            'liveness_score' => $evidence['liveness_score'] ?? null,
        ], $evidence));
    }

    /**
     * Record a raw clock-out event and calculate total worked duration.
     *
     * @param  array<string, mixed>  $evidence
     */
    public function recordClockOut(CarbonInterface|string $time, array $evidence = []): AttendanceEvent
    {
        $clockOut = $time instanceof CarbonInterface ? $time : Carbon::parse($time);
        $this->clock_out = $clockOut;

        if ($this->clock_in) {
            $this->work_duration_minutes = (int) $this->clock_in->diffInMinutes($clockOut);
        }

        $this->save();

        return $this->events()->create(array_merge([
            'event_type' => 'clock_out',
            'recorded_at' => $clockOut,
            'latitude' => $evidence['latitude'] ?? 0.0,
            'longitude' => $evidence['longitude'] ?? 0.0,
            'distance_meters' => $evidence['distance_meters'] ?? 0,
            'is_within_geofence' => $evidence['is_within_geofence'] ?? true,
            'device_info' => $evidence['device_info'] ?? null,
            'photo_path' => $evidence['photo_path'] ?? null,
            'face_matched' => $evidence['face_matched'] ?? false,
            'face_similarity' => $evidence['face_similarity'] ?? null,
            'liveness_score' => $evidence['liveness_score'] ?? null,
        ], $evidence));
    }

    /**
     * Scope query to attendances for a specific date.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForDate(Builder $query, CarbonInterface|string $date): void
    {
        $formattedDate = $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $query->whereDate('date', $formattedDate);
    }
}
