<?php

namespace App\Models;

use App\Services\Leave\LeaveApprovalChainService;
use Database\Factories\LeaveRequestFactory;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'employee_id',
        'doctor_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'rejection_reason',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Associated employee applicant (if employee).
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Associated doctor applicant (if doctor).
     *
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Leave category type.
     *
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Submitter user.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Multi-tier approval logs.
     *
     * @return HasMany<LeaveApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class);
    }

    /**
     * Check if given user is the applicant.
     */
    public function isApplicant(User $user): bool
    {
        if ($this->employee_id && $this->employee?->user_id === $user->id) {
            return true;
        }

        if ($this->doctor_id && $this->doctor?->user_id === $user->id) {
            return true;
        }

        return $this->created_by === $user->id;
    }

    /**
     * Get the dynamic approval chain for this leave request.
     *
     * @return array<int, array{tier: int, title: string, approver_type: string, position_id: ?int, position_title: ?string, is_hr_final: bool}>
     */
    public function getApprovalChain(): array
    {
        return app(LeaveApprovalChainService::class)->buildChain($this);
    }

    /**
     * Get the current pending stage for this leave request.
     *
     * @return array{tier: int, title: string, approver_type: string, position_id: ?int, position_title: ?string, is_hr_final: bool}|null
     */
    public function getCurrentPendingStage(): ?array
    {
        return app(LeaveApprovalChainService::class)->getCurrentPendingStage($this);
    }

    /**
     * Determine whether the given user can approve the current pending stage.
     */
    public function canUserApprove(User $user): bool
    {
        return app(LeaveApprovalChainService::class)->canUserApprove($user, $this);
    }

    /**
     * Advance approval for the current stage via the approval engine.
     */
    public function approveStage(User $approver, ?string $notes = null): void
    {
        app(LeaveApprovalChainService::class)->approveStage($approver, $this, $notes);
    }

    /**
     * Tier 1 Approval (Supervisor / Unit Head).
     */
    public function approveTier1(User $approver, ?string $notes = null): void
    {
        if ($this->isApplicant($approver) && ! $approver->isSuperAdmin()) {
            throw new DomainException('Self-approval violation: Approver cannot approve their own leave request.');
        }

        if ($this->status !== 'pending_tier1') {
            throw new DomainException("Cannot approve Tier 1 from current state: {$this->status}");
        }

        $this->approvals()->create([
            'approver_id' => $approver->id,
            'tier' => 1,
            'status' => 'approved',
            'notes' => $notes,
            'action_at' => Carbon::now(),
        ]);

        $this->update(['status' => 'pending_tier2']);
    }

    /**
     * Tier 2 Final Approval (HR Manager).
     */
    public function approveTier2(User $approver, ?string $notes = null): void
    {
        if ($this->isApplicant($approver) && ! $approver->isSuperAdmin()) {
            throw new DomainException('Self-approval violation: Approver cannot approve their own leave request.');
        }

        if ($this->status !== 'pending_tier2') {
            throw new DomainException("Cannot approve Tier 2 from current state: {$this->status}");
        }

        $this->approvals()->create([
            'approver_id' => $approver->id,
            'tier' => 2,
            'status' => 'approved',
            'notes' => $notes,
            'action_at' => Carbon::now(),
        ]);

        $this->update(['status' => 'approved']);

        // Deduct from balance if applicable
        if ($this->leaveType->deducts_balance) {
            $year = (int) $this->start_date->format('Y');
            $balance = LeaveBalance::where('leave_type_id', $this->leave_type_id)
                ->where('year', $year)
                ->when($this->employee_id, fn ($q) => $q->where('employee_id', $this->employee_id))
                ->when($this->doctor_id, fn ($q) => $q->where('doctor_id', $this->doctor_id))
                ->first();

            if ($balance) {
                $balance->deduct($this->total_days);
            }
        }
    }

    /**
     * Reject leave request at any tier.
     */
    public function reject(User $approver, string $reason, int $tier = 1): void
    {
        if ($this->isApplicant($approver) && ! $approver->isSuperAdmin()) {
            throw new DomainException('Self-approval violation: Approver cannot reject their own leave request as an approver.');
        }

        $this->approvals()->create([
            'approver_id' => $approver->id,
            'tier' => $tier,
            'status' => 'rejected',
            'notes' => $reason,
            'action_at' => Carbon::now(),
        ]);

        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel leave request by applicant before start date.
     */
    public function cancel(User $user): void
    {
        if (! $this->isApplicant($user) && ! $user->isSuperAdmin()) {
            throw new DomainException('Unauthorized: Only the applicant can cancel their leave request.');
        }

        if ($this->status === 'approved' && $this->leaveType->deducts_balance) {
            $year = (int) $this->start_date->format('Y');
            $balance = LeaveBalance::where('leave_type_id', $this->leave_type_id)
                ->where('year', $year)
                ->when($this->employee_id, fn ($q) => $q->where('employee_id', $this->employee_id))
                ->when($this->doctor_id, fn ($q) => $q->where('doctor_id', $this->doctor_id))
                ->first();

            if ($balance) {
                $balance->restore($this->total_days);
            }
        }

        $this->update(['status' => 'cancelled']);
    }
}
