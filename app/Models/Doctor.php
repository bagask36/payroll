<?php

namespace App\Models;

use Database\Factories\DoctorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Doctor extends Model
{
    /** @use HasFactory<DoctorFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'employee_id',
        'name',
        'specialization',
        'str_number',
        'str_valid_until',
        'sip_number',
        'sip_valid_until',
        'phone_number',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'str_valid_until' => 'date',
            'sip_valid_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Doctor $doctor) {
            if (empty($doctor->uuid)) {
                $doctor->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Optional portal user authentication account.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Optional organic hospital employee profile link.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Daily schedule assignments for this doctor.
     *
     * @return HasMany<ScheduleAssignment, $this>
     */
    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }

    /**
     * Leave balance allocations.
     *
     * @return HasMany<LeaveBalance, $this>
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Leave requests submitted by this doctor.
     *
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Associated license and compliance documents (STR, SIP, Certificates).
     *
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Check if the doctor's STR license is expired.
     */
    public function isStrExpired(): bool
    {
        return $this->str_valid_until->isPast();
    }

    /**
     * Check if the doctor's SIP practice license is expired.
     */
    public function isSipExpired(): bool
    {
        return $this->sip_valid_until->isPast();
    }

    /**
     * Check if either STR or SIP license will expire within the given number of days.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        $threshold = Carbon::today()->addDays($days);
        $today = Carbon::today();

        $strExpiring = $this->str_valid_until->isBetween($today, $threshold);
        $sipExpiring = $this->sip_valid_until->isBetween($today, $threshold);

        return $strExpiring || $sipExpiring;
    }

    /**
     * Scope to active status doctors.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }
}
