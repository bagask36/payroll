<?php

namespace App\Livewire\Schedule;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\Unit;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public bool $showAllEmployeesInAssign = false;

    #[Rule(['required', 'in:employee,doctor'])]
    public string $assignTargetType = 'employee';

    public ?int $selectedEmployeeId = null;

    public array $selectedEmployeeIds = [];

    public ?int $selectedDoctorId = null;

    public array $selectedDoctorIds = [];

    #[Rule(['required', 'exists:shifts,id'])]
    public ?int $selectedShiftId = null;

    public string $scheduleDate = '';

    public array $scheduleDates = [];

    public string $dateSelectionMode = 'single'; // 'single', 'range', 'multiple'

    public string $rangeStartDate = '';

    public string $rangeEndDate = '';

    public array $rangeDaysOfWeek = [1, 2, 3, 4, 5, 6, 7]; // 1=Mon .. 7=Sun

    public string $newDateToAdd = '';

    public string $employeeSearch = '';

    public ?int $filterUnitId = null;

    #[Rule(['nullable', 'string', 'max:255'])]
    public string $notes = '';

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', ScheduleAssignment::class);
        $this->weekStartDate = Carbon::today()->startOfWeek()->toDateString();
        $this->scheduleDate = Carbon::today()->toDateString();
        $this->rangeStartDate = Carbon::today()->toDateString();
        $this->rangeEndDate = Carbon::today()->addDays(6)->toDateString();
        $this->rangeDaysOfWeek = [1, 2, 3, 4, 5, 6, 7];
        $this->scheduleDates = [$this->scheduleDate];
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
        $this->scheduleDate = Carbon::today()->toDateString();
        $this->rangeStartDate = Carbon::today()->toDateString();
        $this->rangeEndDate = Carbon::today()->addDays(6)->toDateString();
        $this->rangeDaysOfWeek = [1, 2, 3, 4, 5, 6, 7];
        $this->scheduleDates = [$this->scheduleDate];
        $this->showAssignModal = true;
    }

    public function addSpecificDate(): void
    {
        if (! empty($this->newDateToAdd)) {
            $parsed = Carbon::parse($this->newDateToAdd)->toDateString();
            if (! in_array($parsed, $this->scheduleDates, true)) {
                $this->scheduleDates[] = $parsed;
                sort($this->scheduleDates);
            }
            $this->newDateToAdd = '';
        }
    }

    public function removeSpecificDate(string $date): void
    {
        $this->scheduleDates = array_values(array_filter($this->scheduleDates, fn ($d) => $d !== $date));
    }

    public function toggleDayOfWeek(int $day): void
    {
        if (in_array($day, $this->rangeDaysOfWeek, true)) {
            $this->rangeDaysOfWeek = array_values(array_filter($this->rangeDaysOfWeek, fn ($d) => $d !== $day));
        } else {
            $this->rangeDaysOfWeek[] = $day;
            sort($this->rangeDaysOfWeek);
        }
    }

    public function selectAllFilteredEmployees(array $ids): void
    {
        $this->selectedEmployeeIds = array_values(array_unique(array_merge($this->selectedEmployeeIds, array_map('intval', $ids))));
    }

    public function deselectAllEmployees(): void
    {
        $this->selectedEmployeeIds = [];
        $this->selectedEmployeeId = null;
    }

    public function assignShift(AuditService $auditService): void
    {
        $this->authorize('create', ScheduleAssignment::class);
        $this->resetMessages();

        // 1. Validate Shift and Target Type
        $this->validate([
            'selectedShiftId' => 'required|exists:shifts,id',
            'assignTargetType' => 'required|in:employee,doctor',
            'notes' => 'nullable|string|max:255',
        ]);

        // 2. Resolve Target Personnel
        if ($this->assignTargetType === 'employee') {
            $targetPersonnel = ! empty($this->selectedEmployeeIds)
                ? array_values(array_unique(array_map('intval', $this->selectedEmployeeIds)))
                : ($this->selectedEmployeeId ? [(int) $this->selectedEmployeeId] : []);

            if (empty($targetPersonnel)) {
                $this->errorMessage = 'Silakan pilih setidaknya satu personil perawat/pegawai.';

                return;
            }
        } else {
            $targetPersonnel = ! empty($this->selectedDoctorIds)
                ? array_values(array_unique(array_map('intval', $this->selectedDoctorIds)))
                : ($this->selectedDoctorId ? [(int) $this->selectedDoctorId] : []);

            if (empty($targetPersonnel)) {
                $this->errorMessage = 'Silakan pilih setidaknya satu dokter.';

                return;
            }
        }

        // 3. Resolve Target Dates
        $targetDates = [];
        if ($this->dateSelectionMode === 'range') {
            $this->validate([
                'rangeStartDate' => 'required|date',
                'rangeEndDate' => 'required|date|after_or_equal:rangeStartDate',
            ]);

            if (empty($this->rangeDaysOfWeek)) {
                $this->errorMessage = 'Silakan pilih setidaknya satu hari kerja aktif dalam rentang tanggal.';

                return;
            }

            $start = Carbon::parse($this->rangeStartDate);
            $end = Carbon::parse($this->rangeEndDate);
            $curr = $start->copy();

            while ($curr->lte($end)) {
                if (in_array($curr->dayOfWeekIso, $this->rangeDaysOfWeek, true)) {
                    $targetDates[] = $curr->toDateString();
                }
                $curr->addDay();
            }
        } elseif ($this->dateSelectionMode === 'multiple') {
            if (empty($this->scheduleDates)) {
                $this->errorMessage = 'Silakan tambahkan setidaknya satu tanggal penugasan.';

                return;
            }
            $targetDates = array_values(array_unique($this->scheduleDates));
        } else {
            // Single date mode (default or fallback)
            $this->validate([
                'scheduleDate' => 'required|date',
            ]);
            $targetDates = [$this->scheduleDate];
        }

        if (empty($targetDates)) {
            $this->errorMessage = 'Tidak ada tanggal penugasan yang valid dipilih.';

            return;
        }

        sort($targetDates);

        // 4. Atomic Database Insertions: 1 row per personnel per date per shift
        $createdAssignments = [];
        $conflicts = [];

        DB::transaction(function () use ($targetPersonnel, $targetDates, &$createdAssignments, &$conflicts) {
            foreach ($targetPersonnel as $personId) {
                $empId = $this->assignTargetType === 'employee' ? $personId : null;
                $docId = $this->assignTargetType === 'doctor' ? $personId : null;

                foreach ($targetDates as $dateStr) {
                    if (ScheduleAssignment::hasConflict($empId, $docId, $dateStr)) {
                        $conflicts[] = [
                            'person_id' => $personId,
                            'date' => $dateStr,
                        ];

                        continue;
                    }

                    $assignment = ScheduleAssignment::create([
                        'employee_id' => $empId,
                        'doctor_id' => $docId,
                        'shift_id' => $this->selectedShiftId,
                        'schedule_date' => $dateStr,
                        'status' => 'scheduled',
                        'notes' => $this->notes ?: null,
                        'created_by' => Auth::id(),
                    ]);

                    $createdAssignments[] = $assignment;
                }
            }
        });

        // 5. Handle Conflict Feedback & Success Responses
        if (empty($createdAssignments) && ! empty($conflicts)) {
            $firstDate = $conflicts[0]['date'];
            $this->errorMessage = 'Terdapat bentrok jadwal! Personil ini sudah memiliki jadwal tugas aktif pada tanggal '.$firstDate.'.';

            return;
        }

        $createdCount = count($createdAssignments);
        $conflictCount = count($conflicts);

        if (! empty($createdAssignments)) {
            $auditService->log(
                action: 'schedule.assigned',
                auditable: $createdAssignments[0],
                oldValues: [],
                newValues: [
                    'created_count' => $createdCount,
                    'shift_id' => $this->selectedShiftId,
                    'person_count' => count($targetPersonnel),
                    'date_count' => count($targetDates),
                    'conflicts_skipped' => $conflictCount,
                ]
            );
        }

        $this->showAssignModal = false;
        $msg = "Sebanyak {$createdCount} penugasan shift berhasil diterbitkan.";
        if ($conflictCount > 0) {
            $msg .= " ({$conflictCount} jadwal dilewati karena sudah memiliki jadwal aktif).";
        }
        $this->feedbackMessage = $msg;

        $this->reset([
            'selectedEmployeeId',
            'selectedEmployeeIds',
            'selectedDoctorId',
            'selectedDoctorIds',
            'scheduleDates',
            'notes',
        ]);
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
            ->whereBetween('schedule_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->orderBy('schedule_date', 'asc');

        if ($this->personTypeFilter === 'shift_employee') {
            $query->whereHas('employee', fn ($q) => $q->where('schedule_type', 'shift'));
        } elseif ($this->personTypeFilter === 'employee') {
            $query->whereNotNull('employee_id');
        } elseif ($this->personTypeFilter === 'doctor') {
            $query->whereNotNull('doctor_id');
        }

        if ($this->shiftFilter !== 'all') {
            $query->where('shift_id', $this->shiftFilter);
        }

        $assignments = $query->get();

        $shifts = Shift::where('is_active', true)->orderBy('start_time')->get();

        // Weekly schedule plotting defaults to shift workers
        $employeeQuery = Employee::active()->orderBy('full_name');
        if (! $this->showAllEmployeesInAssign) {
            $employeeQuery->where('schedule_type', 'shift');
        }
        $employees = $employeeQuery->get();
        $totalShiftEmployees = Employee::active()->where('schedule_type', 'shift')->count();

        // Modal multi-selection query with search & unit filter
        $modalEmployeeQuery = Employee::active()->with('unit')->orderBy('full_name');
        if (! $this->showAllEmployeesInAssign) {
            $modalEmployeeQuery->where('schedule_type', 'shift');
        }
        if (! empty($this->employeeSearch)) {
            $term = '%'.$this->employeeSearch.'%';
            $modalEmployeeQuery->where(function ($q) use ($term) {
                $q->where('full_name', 'like', $term)->orWhere('nip', 'like', $term);
            });
        }
        if ($this->filterUnitId) {
            $modalEmployeeQuery->where('unit_id', $this->filterUnitId);
        }
        $assignableEmployees = $modalEmployeeQuery->get();

        $doctors = Doctor::active()->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        // 7 days array for header display
        $weekDays = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        return view('livewire.schedule.schedule-roster', [
            'assignments' => $assignments,
            'shifts' => $shifts,
            'employees' => $employees,
            'assignableEmployees' => $assignableEmployees,
            'totalShiftEmployees' => $totalShiftEmployees,
            'doctors' => $doctors,
            'units' => $units,
            'weekDays' => $weekDays,
            'startDate' => $start,
            'endDate' => $end,
        ]);
    }
}
