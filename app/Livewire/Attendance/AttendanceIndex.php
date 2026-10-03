<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat Presensi - RS Juwita')]
class AttendanceIndex extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public string $startDate = '';

    public string $endDate = '';

    // Adjustment Request Modal State
    public bool $showAdjustmentModal = false;

    public ?int $selectedAttendanceId = null;

    #[Rule(['nullable', 'date_format:H:i'])]
    public string $adjustedClockIn = '';

    #[Rule(['nullable', 'date_format:H:i'])]
    public string $adjustedClockOut = '';

    #[Rule(['required', 'string', 'min:5', 'max:255'])]
    public string $adjustmentReason = '';

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Attendance::class);
        $this->startDate = Carbon::today()->startOfMonth()->toDateString();
        $this->endDate = Carbon::today()->toDateString();
    }

    public function openAdjustmentModal(int $attendanceId): void
    {
        $this->authorize('requestAdjustment', Attendance::class);

        $attendance = Attendance::findOrFail($attendanceId);
        $this->selectedAttendanceId = $attendance->id;
        $this->adjustedClockIn = $attendance->clock_in ? $attendance->clock_in->format('H:i') : '';
        $this->adjustedClockOut = $attendance->clock_out ? $attendance->clock_out->format('H:i') : '';
        $this->adjustmentReason = '';
        $this->showAdjustmentModal = true;
    }

    public function submitAdjustment(AuditService $auditService): void
    {
        $this->authorize('requestAdjustment', Attendance::class);
        $this->validate();

        $attendance = Attendance::findOrFail($this->selectedAttendanceId);
        $user = Auth::user();

        $dateString = $attendance->date->toDateString();
        $newClockIn = $this->adjustedClockIn ? Carbon::parse($dateString.' '.$this->adjustedClockIn) : null;
        $newClockOut = $this->adjustedClockOut ? Carbon::parse($dateString.' '.$this->adjustedClockOut) : null;

        $adjustment = AttendanceAdjustment::create([
            'attendance_id' => $attendance->id,
            'original_clock_in' => $attendance->clock_in,
            'adjusted_clock_in' => $newClockIn,
            'original_clock_out' => $attendance->clock_out,
            'adjusted_clock_out' => $newClockOut,
            'reason' => $this->adjustmentReason,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        $auditService->log(
            action: 'attendance.adjustment_requested',
            auditable: $adjustment,
            oldValues: [],
            newValues: [
                'attendance_id' => $attendance->id,
                'reason' => $this->adjustmentReason,
            ]
        );

        $this->showAdjustmentModal = false;
        $this->feedbackMessage = 'Pengajuan koreksi presensi berhasil dikirimkan ke atasan.';
    }

    public function approveAdjustment(int $adjustmentId, AuditService $auditService): void
    {
        $this->authorize('approveAdjustment', Attendance::class);

        $adjustment = AttendanceAdjustment::findOrFail($adjustmentId);
        $user = Auth::user();

        $adjustment->approve($user);

        $auditService->log(
            action: 'attendance.adjustment_approved',
            auditable: $adjustment,
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'approved', 'approved_by' => $user->id],
        );

        $this->feedbackMessage = 'Koreksi presensi berhasil disetujui.';
    }

    public function render()
    {
        $user = Auth::user();
        $query = Attendance::with(['employee', 'doctor', 'scheduleAssignment.shift', 'adjustments'])
            ->orderBy('date', 'desc');

        // RBAC filtering: if cannot view all, filter to own attendance
        if (! $user->hasPermission('attendance.view_all') && ! $user->hasPermission('attendance.view_team')) {
            $employeeId = $user->employee?->id;
            $doctorId = $user->doctor?->id;

            $query->where(function ($q) use ($employeeId, $doctorId) {
                if ($employeeId) {
                    $q->where('employee_id', $employeeId);
                }
                if ($doctorId) {
                    $q->orWhere('doctor_id', $doctorId);
                }
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->startDate) {
            $query->whereDate('date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('date', '<=', $this->endDate);
        }

        $attendances = $query->paginate(15);

        // Fetch pending adjustments if user can approve
        $pendingAdjustments = $user->hasPermission('attendance.adjust_approve')
            ? AttendanceAdjustment::with(['attendance.employee', 'attendance.doctor', 'requestedBy'])
                ->where('status', 'pending')
                ->latest()
                ->get()
            : collect();

        return view('livewire.attendance.attendance-index', [
            'attendances' => $attendances,
            'pendingAdjustments' => $pendingAdjustments,
        ]);
    }
}
