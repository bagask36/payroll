<?php

namespace App\Livewire\Schedule;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Roster & Jadwal Dinas - RS Juwita')]
class ScheduleRoster extends Component
{
    public string $weekStartDate = '';

    public string $personTypeFilter = 'all';

    public string $shiftFilter = 'all';

    // Assign Shift Modal State
    public bool $showAssignModal = false;

    #[Rule(['required', 'in:employee,doctor'])]
    public string $assignTargetType = 'employee';

    #[Rule(['nullable', 'required_if:assignTargetType,employee', 'exists:employees,id'])]
    public ?int $selectedEmployeeId = null;

    #[Rule(['nullable', 'required_if:assignTargetType,doctor', 'exists:doctors,id'])]
    public ?int $selectedDoctorId = null;

    #[Rule(['required', 'exists:shifts,id'])]
    public ?int $selectedShiftId = null;

    #[Rule(['required', 'date'])]
    public string $scheduleDate = '';

    #[Rule(['nullable', 'string', 'max:255'])]
    public string $notes = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', ScheduleAssignment::class);
        $this->weekStartDate = Carbon::today()->startOfWeek()->toDateString();
        $this->scheduleDate = Carbon::today()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->weekStartDate = Carbon::parse($this->weekStartDate)->subWeek()->startOfWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStartDate = Carbon::parse($this->weekStartDate)->addWeek()->startOfWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->weekStartDate = Carbon::today()->startOfWeek()->toDateString();
    }

    public function openAssignModal(): void
    {
        $this->authorize('create', ScheduleAssignment::class);
        $this->resetValidation();
        $this->resetMessages();
        $this->showAssignModal = true;
    }

    public function assignShift(AuditService $auditService): void
    {
        $this->authorize('create', ScheduleAssignment::class);
        $this->resetMessages();
        $this->validate();

        $employeeId = $this->assignTargetType === 'employee' ? $this->selectedEmployeeId : null;
        $doctorId = $this->assignTargetType === 'doctor' ? $this->selectedDoctorId : null;

        // Conflict check
        if (ScheduleAssignment::hasConflict($employeeId, $doctorId, $this->scheduleDate)) {
            $this->errorMessage = 'Terdapat bentrok jadwal! Personil ini sudah memiliki jadwal tugas aktif pada tanggal '.$this->scheduleDate.'.';

            return;
        }

        $assignment = ScheduleAssignment::create([
            'employee_id' => $employeeId,
            'doctor_id' => $doctorId,
            'shift_id' => $this->selectedShiftId,
            'schedule_date' => $this->scheduleDate,
            'status' => 'scheduled',
            'notes' => $this->notes ?: null,
            'created_by' => Auth::id(),
        ]);

        $auditService->log(
            action: 'schedule.assigned',
            auditable: $assignment,
            oldValues: [],
            newValues: [
                'employee_id' => $employeeId,
                'doctor_id' => $doctorId,
                'shift_id' => $this->selectedShiftId,
                'schedule_date' => $this->scheduleDate,
            ]
        );

        $this->showAssignModal = false;
        $this->feedbackMessage = 'Penugasan shift dinas berhasil diterbitkan.';
        $this->reset(['selectedEmployeeId', 'selectedDoctorId', 'notes']);
        $this->dispatch('pg:eventRefresh-schedules_table');
    }

    #[On('cancelAssignment')]
    public function cancelAssignment(int $assignmentId, AuditService $auditService): void
    {
        $assignment = ScheduleAssignment::findOrFail($assignmentId);
        $this->authorize('delete', $assignment);

        $assignment->update(['status' => 'cancelled']);

        $auditService->log(
            action: 'schedule.cancelled',
            auditable: $assignment,
            oldValues: ['status' => 'scheduled'],
            newValues: ['status' => 'cancelled'],
        );

        $this->dispatch('pg:eventRefresh-schedules_table');
        $this->feedbackMessage = 'Jadwal shift berhasil dibatalkan.';
    }

    protected function resetMessages(): void
    {
        $this->feedbackMessage = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        $start = Carbon::parse($this->weekStartDate)->startOfDay();
        $end = Carbon::parse($this->weekStartDate)->endOfWeek()->endOfDay();

        $query = ScheduleAssignment::with(['shift', 'employee.unit', 'doctor'])
            ->whereBetween('schedule_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->orderBy('schedule_date', 'asc');

        if ($this->personTypeFilter === 'employee') {
            $query->whereNotNull('employee_id');
        } elseif ($this->personTypeFilter === 'doctor') {
            $query->whereNotNull('doctor_id');
        }

        if ($this->shiftFilter !== 'all') {
            $query->where('shift_id', $this->shiftFilter);
        }

        $assignments = $query->get();

        $shifts = Shift::where('is_active', true)->orderBy('start_time')->get();
        $employees = Employee::active()->orderBy('full_name')->get();
        $doctors = Doctor::active()->orderBy('name')->get();

        // 7 days array for header display
        $weekDays = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        return view('livewire.schedule.schedule-roster', [
            'assignments' => $assignments,
            'shifts' => $shifts,
            'employees' => $employees,
            'doctors' => $doctors,
            'weekDays' => $weekDays,
            'startDate' => $start,
            'endDate' => $end,
        ]);
    }
}
