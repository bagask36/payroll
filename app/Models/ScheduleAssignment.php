<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ScheduleAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ScheduleAssignment extends Model
{
    /** @use HasFactory<ScheduleAssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shift_id',
        'employee_id',
        'doctor_id',
        'schedule_date',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
        ];
    }

    /**
     * Associated shift template.
     *
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Assigned employee (if employee).
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Assigned physician (if doctor).
     *
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Supervisor or HR user who published this duty assignment.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if a duplicate schedule assignment exists on the same date for the person.
     */
    public static function hasConflict(?int $employeeId, ?int $doctorId, CarbonInterface|string $date, ?int $ignoreId = null): bool
    {
        $formattedDate = $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $query = static::whereDate('schedule_date', $formattedDate)
            ->where('status', '!=', 'cancelled');

        if ($employeeId !== null) {
            $query->where('employee_id', $employeeId);
        } elseif ($doctorId !== null) {
            $query->where('doctor_id', $doctorId);
        } else {
            return false;
        }

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Scope query to assignments on a specific date.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForDate(Builder $query, CarbonInterface|string $date): void
    {
        $formattedDate = $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $query->whereDate('schedule_date', $formattedDate);
    }

    /**
     * Scope query to assignments for a specific employee.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForEmployee(Builder $query, int $employeeId): void
    {
        $query->where('employee_id', $employeeId);
    }

    /**
     * Scope query to assignments for a specific doctor.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForDoctor(Builder $query, int $doctorId): void
    {
        $query->where('doctor_id', $doctorId);
    }
}
