<?php

declare(strict_types=1);

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use DomainException;

class LeaveApprovalChainService
{
    /**
     * Build the dynamic, ordered approval chain for a given leave request.
     *
     * @return array<int, array{
     *     tier: int,
     *     title: string,
     *     approver_type: string,
     *     position_id: ?int,
     *     position_title: ?string,
     *     is_hr_final: bool
     * }>
     */
    public function buildChain(LeaveRequest $request): array
    {
        $chain = [];
        $tier = 1;

        // 1. Employee applicants: Traverse position hierarchy upwards
        if ($request->employee && $request->employee->position) {
            $currentPos = $request->employee->position;
            $visitedPositionIds = [$currentPos->id];

            while ($currentPos && $currentPos->reports_to_id) {
                $supervisorPos = Position::find($currentPos->reports_to_id);
                if (! $supervisorPos || in_array($supervisorPos->id, $visitedPositionIds, true)) {
                    break;
                }

                $visitedPositionIds[] = $supervisorPos->id;

                $chain[] = [
                    'tier' => $tier++,
                    'title' => "Persetujuan {$supervisorPos->title}",
                    'approver_type' => 'position',
                    'position_id' => $supervisorPos->id,
                    'position_title' => $supervisorPos->title,
                    'is_hr_final' => false,
                ];

                $grade = strtolower($supervisorPos->grade_level ?? '');
                $posTitleLower = strtolower($supervisorPos->title);

                // Stop traversing upwards if we reached Direktur
                if ($grade === 'director' || str_contains($posTitleLower, 'direktur')) {
                    break;
                }

                // If supervisor is Kabag / Kabid (Division Head),
                // staff leave stops here before routing to HR final.
                // However, if the applicant was a Unit Head (Karu/PJ), continue one more step to Direktur if configured.
                if ($grade === 'manager' || str_contains($posTitleLower, 'kepala bagian') || str_contains($posTitleLower, 'kepala bidang')) {
                    // Check if applicant is Kabag/Kabid itself (they report to Direktur)
                    $applicantGrade = strtolower($request->employee->position->grade_level ?? '');
                    if ($applicantGrade === 'manager') {
                        // The while loop will continue to Direktur
                    } else {
                        // Regular staff stops at Kabag/Kabid
                        break;
                    }
                }

                $currentPos = $supervisorPos;
            }
        }

        // 2. Doctor applicants: Komite Medik -> Kabid Pelayanan Medik
        if ($request->doctor_id && empty($chain)) {
            $komiteMedikPos = Position::where('title', 'like', '%Komite Medik%')->first();
            if ($komiteMedikPos) {
                $chain[] = [
                    'tier' => $tier++,
                    'title' => "Persetujuan {$komiteMedikPos->title}",
                    'approver_type' => 'position',
                    'position_id' => $komiteMedikPos->id,
                    'position_title' => $komiteMedikPos->title,
                    'is_hr_final' => false,
                ];
            }

            $yanmedPos = Position::where('title', 'like', '%Pelayanan Medik%')->first();
            if ($yanmedPos) {
                $chain[] = [
                    'tier' => $tier++,
                    'title' => "Persetujuan {$yanmedPos->title}",
                    'approver_type' => 'position',
                    'position_id' => $yanmedPos->id,
                    'position_title' => $yanmedPos->title,
                    'is_hr_final' => false,
                ];
            }
        }

        // 3. Fallback if no structural supervisor position is found
        if (empty($chain)) {
            $chain[] = [
                'tier' => $tier++,
                'title' => 'Persetujuan Atasan / Kepala Unit',
                'approver_type' => 'supervisor_general',
                'position_id' => null,
                'position_title' => 'Atasan Langsung',
                'is_hr_final' => false,
            ];
        }

        // 4. Final step is ALWAYS HR Management (Bagian SDM)
        $chain[] = [
            'tier' => $tier,
            'title' => 'Persetujuan Final HR / Bagian SDM',
            'approver_type' => 'hr_manager',
            'position_id' => null,
            'position_title' => 'Kepala Bagian SDM / HR Manager',
            'is_hr_final' => true,
        ];

        return $chain;
    }

    /**
     * Get the current pending stage for this leave request.
     *
     * @return array{
     *     tier: int,
     *     title: string,
     *     approver_type: string,
     *     position_id: ?int,
     *     position_title: ?string,
     *     is_hr_final: bool
     * }|null
     */
    public function getCurrentPendingStage(LeaveRequest $request): ?array
    {
        if (in_array($request->status, ['approved', 'rejected', 'cancelled'], true)) {
            return null;
        }

        $chain = $this->buildChain($request);
        $approvedCount = $request->approvals()->where('status', 'approved')->count();

        return $chain[$approvedCount] ?? null;
    }

    /**
     * Determine whether the given user can approve the current pending stage.
     */
    public function canUserApprove(User $user, LeaveRequest $request): bool
    {
        // 1. Anti-self-approval: applicant cannot approve own leave
        if ($request->isApplicant($user) && ! $user->isSuperAdmin()) {
            return false;
        }

        // 2. Super Admin has universal emergency override
        if ($user->isSuperAdmin()) {
            return true;
        }

        $currentStage = $this->getCurrentPendingStage($request);
        if (! $currentStage) {
            return false;
        }

        // 3. Final HR stage
        if ($currentStage['is_hr_final']) {
            return $user->hasRole('hr_manager')
                || $user->hasRole('super_admin')
                || $user->hasPermission('leave.approve_tier2');
        }

        // 4. Structural Position stage
        if ($currentStage['approver_type'] === 'position' && $currentStage['position_id']) {
            if ($user->employee && $user->employee->position_id === $currentStage['position_id']) {
                return true;
            }

            // Also allow HR Manager or designated Unit Supervisor
            if ($user->hasRole('hr_manager')) {
                return true;
            }

            return false;
        }

        // 5. Fallback general supervisor stage
        if ($user->hasPermission('leave.approve_tier1')) {
            if ($user->employee && $request->employee) {
                return $user->employee->isSupervisorOf($request->employee);
            }

            return true;
        }

        return false;
    }

    /**
     * Advance approval for the current stage.
     */
    public function approveStage(User $approver, LeaveRequest $request, ?string $notes = null): void
    {
        if (! $this->canUserApprove($approver, $request)) {
            throw new DomainException('Otorisasi ditolak: Anda tidak memiliki wewenang untuk menyetujui tahapan cuti ini.');
        }

        $currentStage = $this->getCurrentPendingStage($request);
        if (! $currentStage) {
            throw new DomainException('Permohonan cuti ini tidak memiliki tahapan persetujuan yang aktif.');
        }

        // Record approval transaction
        $request->approvals()->create([
            'approver_id' => $approver->id,
            'tier' => $currentStage['tier'],
            'status' => 'approved',
            'notes' => $notes,
            'action_at' => Carbon::now(),
        ]);

        $chain = $this->buildChain($request);
        $approvedCount = $request->approvals()->where('status', 'approved')->count();

        // Check if there are more stages
        if ($approvedCount < count($chain)) {
            $nextStage = $chain[$approvedCount];
            // If next stage is HR, set to pending_tier2 (for backward compatibility), otherwise intermediate pending
            $status = $nextStage['is_hr_final'] ? 'pending_tier2' : 'pending_tier1';
            $request->update(['status' => $status]);
        } else {
            // Final stage completed -> APPROVE & DEDUCT QUOTA
            $request->update(['status' => 'approved']);

            if ($request->leaveType && $request->leaveType->deducts_balance) {
                $year = (int) $request->start_date->format('Y');
                $balance = LeaveBalance::where('leave_type_id', $request->leave_type_id)
                    ->where('year', $year)
                    ->when($request->employee_id, fn ($q) => $q->where('employee_id', $request->employee_id))
                    ->when($request->doctor_id, fn ($q) => $q->where('doctor_id', $request->doctor_id))
                    ->first();

                if ($balance) {
                    $balance->deduct($request->total_days);
                }
            }
        }
    }
}
