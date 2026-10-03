<?php

namespace App\Models;

use Database\Factories\AttendanceAdjustmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AttendanceAdjustment extends Model
{
    /** @use HasFactory<AttendanceAdjustmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'attendance_id',
        'original_clock_in',
        'adjusted_clock_in',
        'original_clock_out',
        'adjusted_clock_out',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_clock_in' => 'datetime',
            'adjusted_clock_in' => 'datetime',
            'original_clock_out' => 'datetime',
            'adjusted_clock_out' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Associated attendance record.
     *
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * User who requested the adjustment.
     *
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Supervisor or HR who authorized the adjustment.
     *
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve the adjustment and update the parent attendance record with audit trail.
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => Carbon::now(),
        ]);

        $attendance = $this->attendance;
        if ($attendance) {
            if ($this->adjusted_clock_in) {
                $attendance->clock_in = $this->adjusted_clock_in;
            }
            if ($this->adjusted_clock_out) {
                $attendance->clock_out = $this->adjusted_clock_out;
            }

            if ($attendance->clock_in && $attendance->clock_out) {
                $attendance->work_duration_minutes = (int) $attendance->clock_in->diffInMinutes($attendance->clock_out);
            }

            $attendance->save();
        }
    }
}
