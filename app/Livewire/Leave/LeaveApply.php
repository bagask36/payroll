<?php

namespace App\Livewire\Leave;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pengajuan Cuti / Izin - RS Juwita')]
class LeaveApply extends Component
{
    #[Rule(['required', 'exists:leave_types,id'])]
    public ?int $leaveTypeId = null;

    #[Rule(['required', 'date', 'after_or_equal:today'])]
    public string $startDate = '';

    #[Rule(['required', 'date', 'after_or_equal:startDate'])]
    public string $endDate = '';

    #[Rule(['required', 'string', 'min:5', 'max:255'])]
    public string $reason = '';

    public ?float $availableBalance = null;

    public float $calculatedDays = 1.0;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('create', LeaveRequest::class);
        $this->startDate = Carbon::today()->addDay()->toDateString();
        $this->endDate = Carbon::today()->addDay()->toDateString();

        $defaultType = LeaveType::active()->first();
        if ($defaultType) {
            $this->leaveTypeId = $defaultType->id;
            $this->updateBalance();
        }
        $this->calculateDays();
    }

    public function updatedLeaveTypeId(): void
    {
        $this->updateBalance();
    }

    public function updatedStartDate(): void
    {
        $this->calculateDays();
    }

    public function updatedEndDate(): void
    {
        $this->calculateDays();
    }

    public function updateBalance(): void
    {
        $user = Auth::user();
        $year = (int) Carbon::today()->format('Y');

        $employeeId = $user?->employee?->id;
        $doctorId = $user?->doctor?->id;

        if (! $this->leaveTypeId) {
            $this->availableBalance = null;

            return;
        }

        $type = LeaveType::find($this->leaveTypeId);
        if ($type && ! $type->deducts_balance) {
            $this->availableBalance = 99.0; // Unlimited or non-quota based (e.g. sick leave)

            return;
        }

        $balance = LeaveBalance::where('leave_type_id', $this->leaveTypeId)
            ->where('year', $year)
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->first();

        $this->availableBalance = $balance ? (float) $balance->remaining_days : 0.0;
    }

    public function calculateDays(): void
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            if ($end->greaterThanOrEqualTo($start)) {
                // Total calendar days inclusive
                $this->calculatedDays = (float) ($start->diffInDays($end) + 1);
            }
        }
    }

    public function submit(AuditService $auditService)
    {
        $this->authorize('create', LeaveRequest::class);
        $this->errorMessage = null;
        $this->validate();

        $user = Auth::user();
        $employeeId = $user?->employee?->id;
        $doctorId = $user?->doctor?->id;

        $type = LeaveType::findOrFail($this->leaveTypeId);

        // Balance validation
        if ($type->deducts_balance) {
            $this->updateBalance();
            if ($this->availableBalance !== null && $this->calculatedDays > $this->availableBalance) {
                $this->errorMessage = "Saldo cuti tidak mencukupi! Sisa kuota Anda adalah {$this->availableBalance} hari, sedangkan pengajuan membutuhkan {$this->calculatedDays} hari.";

                return;
            }
        }

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employeeId,
            'doctor_id' => $doctorId,
            'leave_type_id' => $type->id,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'total_days' => $this->calculatedDays,
            'reason' => $this->reason,
            'status' => 'pending_tier1',
            'created_by' => $user->id,
        ]);

        $auditService->log(
            action: 'leave.submitted',
            auditable: $leaveRequest,
            oldValues: [],
            newValues: [
                'type' => $type->name,
                'days' => $this->calculatedDays,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]
        );

        session()->flash('success', 'Permohonan cuti berhasil diajukan dan sedang menunggu persetujuan atasan (Tier 1).');

        return $this->redirectRoute('leaves.index', navigate: true);
    }

    public function render()
    {
        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        return view('livewire.leave.leave-apply', [
            'leaveTypes' => $leaveTypes,
        ]);
    }
}
