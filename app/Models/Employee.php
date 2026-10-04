<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'unit_id',
        'position_id',
        'nip',
        'ktp_number',
        'full_name',
        'gender',
        'birth_place',
        'birth_date',
        'marital_status',
        'tax_dependents',
        'npwp_number',
        'phone_number',
        'residential_address',
        'face_biometric_vector',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'tax_dependents' => 'integer',
            'ktp_number' => 'encrypted',
            'face_biometric_vector' => 'encrypted:array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (empty($employee->uuid)) {
                $employee->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Optional user account link.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Unit organization assignment.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Position assignment.
     *
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Employment contracts history.
     *
     * @return HasMany<EmployeeEmployment, $this>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(EmployeeEmployment::class)->orderByDesc('start_date')->orderByDesc('id');
    }

    /**
     * Current active employment contract.
     *
     * @return HasOne<EmployeeEmployment, $this>
     */
    public function currentEmployment(): HasOne
    {
        return $this->hasOne(EmployeeEmployment::class)->where('is_active', true)->latestOfMany();
    }

    /**
     * Basic salary revision history.
     *
     * @return HasMany<EmployeeSalary, $this>
     */
    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    /**
     * Currently active base salary.
     *
     * @return HasOne<EmployeeSalary, $this>
     */
    public function currentSalary(): HasOne
    {
        return $this->hasOne(EmployeeSalary::class)->latestOfMany('effective_from');
    }

    /**
     * All assigned recurring salary components (allowances & deductions).
     *
     * @return HasMany<EmployeeSalaryComponent, $this>
     */
    public function salaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    /**
     * Currently active recurring salary components.
     *
     * @return HasMany<EmployeeSalaryComponent, $this>
     */
    public function activeSalaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            });
    }

    /**
     * Disbursement bank accounts.
     *
     * @return HasMany<EmployeeBankAccount, $this>
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    /**
     * Primary salary disbursement bank account.
     *
     * @return HasOne<EmployeeBankAccount, $this>
     */
    public function primaryBankAccount(): HasOne
    {
        return $this->hasOne(EmployeeBankAccount::class)->where('is_primary', true)->latestOfMany();
    }

    /**
     * Associated doctor profile if practicing physician.
     *
     * @return HasOne<Doctor, $this>
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * Daily schedule assignments for this employee.
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
     * Leave requests submitted by this employee.
     *
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Associated compliance and personal documents.
     *
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Get employee name accessor (alias of full_name).
     */
    public function getNameAttribute(): string
    {
        return $this->full_name ?? '';
    }

    /**
     * Get employee phone accessor.
     */
    public function getPhoneAttribute(): string
    {
        return $this->phone_number ?? '';
    }

    /**
     * Get employee email accessor.
     */
    public function getEmailAttribute(): string
    {
        return $this->user?->email ?? '-';
    }

    /**
     * Get employee address accessor.
     */
    public function getAddressAttribute(): string
    {
        return $this->residential_address ?? '';
    }

    /**
     * Filter query to active employees only.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Get direct supervisor employees based on position hierarchy or unit leadership.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    public function getDirectSupervisors(): \Illuminate\Database\Eloquent\Collection
    {
        // 1. Direct match via position reports_to_id
        if ($this->position?->reports_to_id) {
            $supervisors = self::where('position_id', $this->position->reports_to_id)
                ->where('status', 'active')
                ->where('id', '!=', $this->id)
                ->get();

            if ($supervisors->isNotEmpty()) {
                return $supervisors;
            }
        }

        // 2. Fallback: Unit Supervisor/Manager within the same unit
        if ($this->unit_id) {
            return self::where('unit_id', $this->unit_id)
                ->where('id', '!=', $this->id)
                ->where('status', 'active')
                ->whereHas('position', function ($q) {
                    $q->whereIn('grade_level', ['supervisor', 'manager', 'head']);
                })
                ->get();
        }

        return self::whereRaw('1 = 0')->get();
    }

    /**
     * Check if this employee is a supervisor of the target employee.
     */
    public function isSupervisorOf(Employee $subordinate): bool
    {
        if ($this->id === $subordinate->id) {
            return false;
        }

        // 1. Direct match via position reports_to_id
        if ($subordinate->position?->reports_to_id && $subordinate->position->reports_to_id === $this->position_id) {
            return true;
        }

        // 2. Unit supervisor match: same unit and this employee holds a supervisor/manager grade
        if ($this->unit_id && $this->unit_id === $subordinate->unit_id) {
            $grade = strtolower($this->position?->grade_level ?? '');
            if (in_array($grade, ['supervisor', 'manager', 'head'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all subordinate employees reporting to this employee.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    public function getSubordinates(): \Illuminate\Database\Eloquent\Collection
    {
        $subordinatePositionIds = Position::where('reports_to_id', $this->position_id)->pluck('id');

        return self::where('status', 'active')
            ->where('id', '!=', $this->id)
            ->where(function ($q) use ($subordinatePositionIds) {
                if ($subordinatePositionIds->isNotEmpty()) {
                    $q->whereIn('position_id', $subordinatePositionIds);
                }
                if ($this->unit_id && in_array(strtolower($this->position?->grade_level ?? ''), ['supervisor', 'manager', 'head'])) {
                    $q->orWhere('unit_id', $this->unit_id);
                }
            })
            ->get();
    }

    /**
     * Warning letters / disciplinary sanctions received by this employee.
     *
     * @return HasMany<EmployeeWarningLetter, $this>
     */
    public function warningLetters(): HasMany
    {
        return $this->hasMany(EmployeeWarningLetter::class)->orderByDesc('issued_date');
    }

    /**
     * Currently active warning letters.
     */
    public function activeWarningLetters(): HasMany
    {
        return $this->warningLetters()->active();
    }

    /**
     * Check if employee has an active warning letter.
     */
    public function hasActiveWarningLetter(): bool
    {
        return $this->activeWarningLetters()->exists();
    }
}

