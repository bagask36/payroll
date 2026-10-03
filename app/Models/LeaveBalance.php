<?php

namespace App\Models;

use Database\Factories\LeaveBalanceFactory;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    /** @use HasFactory<LeaveBalanceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'doctor_id',
        'leave_type_id',
        'year',
        'assigned_days',
        'used_days',
        'remaining_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'assigned_days' => 'float',
            'used_days' => 'float',
            'remaining_days' => 'float',
        ];
    }

    /**
     * Associated employee.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Associated doctor.
     *
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Associated leave type category.
     *
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Deduct days from this balance.
     */
    public function deduct(float $days): void
    {
        if ($this->remaining_days < $days) {
            throw new DomainException("Insufficient leave balance. Remaining: {$this->remaining_days}, Requested: {$days}");
        }

        $this->used_days += $days;
        $this->remaining_days -= $days;
        $this->save();
    }

    /**
     * Restore previously deducted days.
     */
    public function restore(float $days): void
    {
        $this->used_days = (float) max(0, $this->used_days - $days);
        $this->remaining_days += $days;
        $this->save();
    }
}
