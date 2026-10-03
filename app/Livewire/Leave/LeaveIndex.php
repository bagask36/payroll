<?php

namespace App\Livewire\Leave;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manajemen Cuti & Izin - RS Juwita')]
class LeaveIndex extends Component
{
    use WithPagination;

    public string $activeTab = 'my_requests';

    public string $statusFilter = 'all';

    // Rejection Modal State
    public bool $showRejectModal = false;

    public ?int $targetRequestId = null;

    public int $targetTier = 1;

    #[Rule(['required', 'string', 'min:5', 'max:255'])]
    public string $rejectionReason = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $user = Auth::user();
        if (session()->has('success')) {
            $this->feedbackMessage = session('success');
        }

        // If user is HR Manager and has no personal requests, or has tier2 approvals, default to tier2 or tier1
        if ($user->hasPermission('leave.approve_tier1') && ! $user->employee && ! $user->doctor) {
            $this->activeTab = 'tier1_approvals';
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetMessages();
    }

    public function cancelRequest(int $requestId, AuditService $auditService): void
    {
        $user = Auth::user();
        $request = LeaveRequest::findOrFail($requestId);

        $this->authorize('cancel', $request);

        $request->cancel($user);

        $auditService->log(
            action: 'leave.cancelled',
            auditable: $request,
            oldValues: ['status' => 'pending_tier1'],
            newValues: ['status' => 'cancelled'],
        );

        $this->feedbackMessage = 'Pengajuan cuti berhasil dibatalkan.';
    }

    public function approveTier1(int $requestId, AuditService $auditService): void
    {
        $user = Auth::user();
        $request = LeaveRequest::findOrFail($requestId);

        $this->authorize('approveTier1', $request);

        try {
            $request->approveTier1($user);

            $auditService->log(
                action: 'leave.approved_tier1',
                auditable: $request,
                oldValues: ['status' => 'pending_tier1'],
                newValues: ['status' => 'pending_tier2', 'approved_by_tier1' => $user->id],
            );

            $this->feedbackMessage = 'Persetujuan Tier 1 berhasil diberikan. Permohonan diteruskan ke Manajemen SDM (Tier 2).';
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function approveTier2(int $requestId, AuditService $auditService): void
    {
        $user = Auth::user();
        $request = LeaveRequest::findOrFail($requestId);

        $this->authorize('approveTier2', $request);

        try {
            $request->approveTier2($user);

            $auditService->log(
                action: 'leave.approved_tier2',
                auditable: $request,
                oldValues: ['status' => 'pending_tier2'],
                newValues: ['status' => 'approved', 'approved_by_tier2' => $user->id],
            );

            $this->feedbackMessage = 'Persetujuan Final Tier 2 berhasil diberikan. Saldo kuota cuti telah dipotong.';
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function openRejectModal(int $requestId, int $tier): void
    {
        $this->targetRequestId = $requestId;
        $this->targetTier = $tier;
        $this->rejectionReason = '';
        $this->resetValidation();
        $this->showRejectModal = true;
    }

    public function submitRejection(AuditService $auditService): void
    {
        $user = Auth::user();
        $request = LeaveRequest::findOrFail($this->targetRequestId);

        if ($this->targetTier === 1) {
            $this->authorize('approveTier1', $request);
        } else {
            $this->authorize('approveTier2', $request);
        }

        $this->validate();

        try {
            $request->reject($user, $this->rejectionReason, $this->targetTier);

            $auditService->log(
                action: 'leave.rejected',
                auditable: $request,
                oldValues: ['status' => $request->getOriginal('status')],
                newValues: ['status' => 'rejected', 'reason' => $this->rejectionReason],
            );

            $this->showRejectModal = false;
            $this->feedbackMessage = 'Permohonan cuti telah ditolak.';
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    protected function resetMessages(): void
    {
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        $user = Auth::user();
        $employeeId = $user?->employee?->id;
        $doctorId = $user?->doctor?->id;
        $year = (int) Carbon::today()->format('Y');

        // Balances for the logged in person
        $balances = LeaveBalance::with('leaveType')
            ->where('year', $year)
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->get();

        // My Personal Requests
        $myRequestsQuery = LeaveRequest::with(['leaveType', 'approvals.approver'])
            ->where(function ($q) use ($employeeId, $doctorId, $user) {
                if ($employeeId) {
                    $q->where('employee_id', $employeeId);
                } elseif ($doctorId) {
                    $q->where('doctor_id', $doctorId);
                } else {
                    $q->where('created_by', $user->id);
                }
            })
            ->latest();

        if ($this->statusFilter !== 'all') {
            $myRequestsQuery->where('status', $this->statusFilter);
        }
        $myRequests = $myRequestsQuery->paginate(10);

        // Tier 1 Approvals Queue (Supervisor)
        $tier1Queue = $user->hasPermission('leave.approve_tier1')
            ? LeaveRequest::with(['employee', 'doctor', 'leaveType'])
                ->where('status', 'pending_tier1')
                ->where('created_by', '!=', $user->id)
                ->latest()
                ->get()
            : collect();

        // Tier 2 Approvals Queue (HR Manager)
        $tier2Queue = $user->hasPermission('leave.approve_tier2')
            ? LeaveRequest::with(['employee', 'doctor', 'leaveType', 'approvals.approver'])
                ->where('status', 'pending_tier2')
                ->where('created_by', '!=', $user->id)
                ->latest()
                ->get()
            : collect();

        return view('livewire.leave.leave-index', [
            'balances' => $balances,
            'myRequests' => $myRequests,
            'tier1Queue' => $tier1Queue,
            'tier2Queue' => $tier2Queue,
        ]);
    }
}
