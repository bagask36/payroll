<?php

namespace App\Livewire\Master;

use App\Models\BpjsRule;
use App\Models\Department;
use App\Models\GeofenceLocation;
use App\Models\LeaveType;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Models\Shift;
use App\Models\Unit;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Master Data Rumah Sakit - RS Juwita')]
class MasterIndex extends Component
{
    use WithPagination;

    public string $activeTab = 'units';

    public string $search = '';

    // Unit Form State
    public bool $showUnitModal = false;

    public ?int $editingUnitId = null;

    public string $unitCode = '';

    public string $unitName = '';

    public ?int $unitDepartmentId = null;

    public bool $unitIsActive = true;

    // Position Form State
    public bool $showPositionModal = false;

    public ?int $editingPositionId = null;

    public string $positionTitle = '';

    public ?int $positionUnitId = null;

    public string $positionGradeLevel = 'III/a';

    // Shift Form State
    public bool $showShiftModal = false;

    public ?int $editingShiftId = null;

    public string $shiftCode = '';

    public string $shiftName = '';

    public string $shiftStartTime = '07:00';

    public string $shiftEndTime = '14:00';

    public bool $shiftIsOvernight = false;

    public int $shiftLateTolerance = 15;

    public bool $shiftIsActive = true;

    // Leave Type Form State
    public bool $showLeaveTypeModal = false;

    public ?int $editingLeaveTypeId = null;

    public string $leaveTypeCode = '';

    public string $leaveTypeName = '';

    public int $leaveTypeDefaultDays = 12;

    public bool $leaveTypeDeductsBalance = true;

    public bool $leaveTypeRequiresAttachment = false;

    public bool $leaveTypeIsActive = true;

    // Geofence Form State
    public bool $showGeofenceModal = false;

    public ?int $editingGeofenceId = null;

    public string $geofenceName = '';

    public float $geofenceLat = -6.2088;

    public float $geofenceLng = 106.8456;

    public int $geofenceRadius = 150;

    public bool $geofenceIsActive = true;

    // Payroll Period Form State
    public bool $showPeriodModal = false;

    public ?int $editingPeriodId = null;

    public string $periodName = '';

    public string $periodStartDate = '';

    public string $periodEndDate = '';

    public string $periodCutoffDate = '';

    public bool $periodIsClosed = false;

    // BPJS Rule Form State
    public bool $showBpjsModal = false;

    public ?int $editingBpjsId = null;

    public string $bpjsProgramName = '';

    public float $bpjsEmployeeRate = 0.01;

    public float $bpjsEmployerRate = 0.04;

    public ?float $bpjsWageCeiling = null;

    public bool $bpjsIsActive = true;

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasPermission('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke master data dibatasi khusus Administrator HR dan Super Admin.');
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->resetPage();
    }

    // --- UNIT ACTIONS ---
    public function openCreateUnit(): void
    {
        $this->reset(['editingUnitId', 'unitCode', 'unitName', 'unitDepartmentId']);
        $this->unitIsActive = true;
        $this->showUnitModal = true;
    }

    public function editUnit(int $id): void
    {
        $unit = Unit::findOrFail($id);
        $this->editingUnitId = $unit->id;
        $this->unitCode = $unit->code;
        $this->unitName = $unit->name;
        $this->unitDepartmentId = $unit->department_id;
        $this->unitIsActive = $unit->is_active;
        $this->showUnitModal = true;
    }

    public function saveUnit(AuditService $auditService): void
    {
        $this->validate([
            'unitCode' => 'required|string|max:20|unique:units,code,'.$this->editingUnitId,
            'unitName' => 'required|string|max:100',
            'unitDepartmentId' => 'required|exists:departments,id',
        ]);

        if ($this->editingUnitId) {
            $unit = Unit::findOrFail($this->editingUnitId);
            $unit->update([
                'code' => $this->unitCode,
                'name' => $this->unitName,
                'department_id' => $this->unitDepartmentId,
                'is_active' => $this->unitIsActive,
            ]);
            $action = 'unit.updated';
        } else {
            $unit = Unit::create([
                'code' => $this->unitCode,
                'name' => $this->unitName,
                'department_id' => $this->unitDepartmentId,
                'is_active' => $this->unitIsActive,
            ]);
            $action = 'unit.created';
        }

        $auditService->log(action: $action, auditable: $unit, newValues: ['name' => $unit->name]);
        $this->showUnitModal = false;
        $this->feedbackMessage = "Unit kerja {$unit->name} berhasil disimpan!";
    }

    // --- POSITION ACTIONS ---
    public function openCreatePosition(): void
    {
        $this->reset(['editingPositionId', 'positionTitle', 'positionUnitId']);
        $this->positionGradeLevel = 'III/a';
        $this->showPositionModal = true;
    }

    public function editPosition(int $id): void
    {
        $pos = Position::findOrFail($id);
        $this->editingPositionId = $pos->id;
        $this->positionTitle = $pos->title;
        $this->positionUnitId = $pos->unit_id;
        $this->positionGradeLevel = $pos->grade_level ?? 'III/a';
        $this->showPositionModal = true;
    }

    public function savePosition(AuditService $auditService): void
    {
        $this->validate([
            'positionTitle' => 'required|string|max:100',
            'positionUnitId' => 'nullable|exists:units,id',
            'positionGradeLevel' => 'nullable|string|max:20',
        ]);

        if ($this->editingPositionId) {
            $pos = Position::findOrFail($this->editingPositionId);
            $pos->update([
                'title' => $this->positionTitle,
                'unit_id' => $this->positionUnitId,
                'grade_level' => $this->positionGradeLevel,
            ]);
            $action = 'position.updated';
        } else {
            $pos = Position::create([
                'title' => $this->positionTitle,
                'unit_id' => $this->positionUnitId,
                'grade_level' => $this->positionGradeLevel,
            ]);
            $action = 'position.created';
        }

        $auditService->log(action: $action, auditable: $pos, newValues: ['title' => $pos->title]);
        $this->showPositionModal = false;
        $this->feedbackMessage = "Jabatan {$pos->title} berhasil disimpan!";
    }

    // --- SHIFT ACTIONS ---
    public function openCreateShift(): void
    {
        $this->reset(['editingShiftId', 'shiftCode', 'shiftName', 'shiftStartTime', 'shiftEndTime']);
        $this->shiftIsOvernight = false;
        $this->shiftLateTolerance = 15;
        $this->shiftIsActive = true;
        $this->showShiftModal = true;
    }

    public function editShift(int $id): void
    {
        $shift = Shift::findOrFail($id);
        $this->editingShiftId = $shift->id;
        $this->shiftCode = $shift->code;
        $this->shiftName = $shift->name;
        $this->shiftStartTime = substr($shift->start_time, 0, 5);
        $this->shiftEndTime = substr($shift->end_time, 0, 5);
        $this->shiftIsOvernight = $shift->is_overnight;
        $this->shiftLateTolerance = $shift->late_tolerance_minutes;
        $this->shiftIsActive = $shift->is_active;
        $this->showShiftModal = true;
    }

    public function saveShift(AuditService $auditService): void
    {
        $this->validate([
            'shiftCode' => 'required|string|max:20|unique:shifts,code,'.$this->editingShiftId,
            'shiftName' => 'required|string|max:100',
            'shiftStartTime' => 'required|date_format:H:i',
            'shiftEndTime' => 'required|date_format:H:i',
            'shiftLateTolerance' => 'required|integer|min:0|max:120',
        ]);

        if ($this->editingShiftId) {
            $shift = Shift::findOrFail($this->editingShiftId);
            $shift->update([
                'code' => $this->shiftCode,
                'name' => $this->shiftName,
                'start_time' => $this->shiftStartTime,
                'end_time' => $this->shiftEndTime,
                'is_overnight' => $this->shiftIsOvernight,
                'late_tolerance_minutes' => $this->shiftLateTolerance,
                'is_active' => $this->shiftIsActive,
            ]);
            $action = 'shift.updated';
        } else {
            $shift = Shift::create([
                'code' => $this->shiftCode,
                'name' => $this->shiftName,
                'start_time' => $this->shiftStartTime,
                'end_time' => $this->shiftEndTime,
                'is_overnight' => $this->shiftIsOvernight,
                'late_tolerance_minutes' => $this->shiftLateTolerance,
                'is_active' => $this->shiftIsActive,
            ]);
            $action = 'shift.created';
        }

        $auditService->log(action: $action, auditable: $shift, newValues: ['name' => $shift->name]);
        $this->showShiftModal = false;
        $this->feedbackMessage = "Shift kerja {$shift->name} berhasil disimpan!";
    }

    // --- LEAVE TYPE ACTIONS ---
    public function openCreateLeaveType(): void
    {
        $this->reset(['editingLeaveTypeId', 'leaveTypeCode', 'leaveTypeName']);
        $this->leaveTypeDefaultDays = 12;
        $this->leaveTypeDeductsBalance = true;
        $this->leaveTypeRequiresAttachment = false;
        $this->leaveTypeIsActive = true;
        $this->showLeaveTypeModal = true;
    }

    public function editLeaveType(int $id): void
    {
        $lt = LeaveType::findOrFail($id);
        $this->editingLeaveTypeId = $lt->id;
        $this->leaveTypeCode = $lt->code;
        $this->leaveTypeName = $lt->name;
        $this->leaveTypeDefaultDays = $lt->default_days_per_year;
        $this->leaveTypeDeductsBalance = $lt->deducts_balance;
        $this->leaveTypeRequiresAttachment = $lt->requires_attachment;
        $this->leaveTypeIsActive = $lt->is_active;
        $this->showLeaveTypeModal = true;
    }

    public function saveLeaveType(AuditService $auditService): void
    {
        $this->validate([
            'leaveTypeCode' => 'required|string|max:20|unique:leave_types,code,'.$this->editingLeaveTypeId,
            'leaveTypeName' => 'required|string|max:100',
            'leaveTypeDefaultDays' => 'required|integer|min:0|max:365',
        ]);

        if ($this->editingLeaveTypeId) {
            $lt = LeaveType::findOrFail($this->editingLeaveTypeId);
            $lt->update([
                'code' => $this->leaveTypeCode,
                'name' => $this->leaveTypeName,
                'default_days_per_year' => $this->leaveTypeDefaultDays,
                'deducts_balance' => $this->leaveTypeDeductsBalance,
                'requires_attachment' => $this->leaveTypeRequiresAttachment,
                'is_active' => $this->leaveTypeIsActive,
            ]);
            $action = 'leave_type.updated';
        } else {
            $lt = LeaveType::create([
                'code' => $this->leaveTypeCode,
                'name' => $this->leaveTypeName,
                'default_days_per_year' => $this->leaveTypeDefaultDays,
                'deducts_balance' => $this->leaveTypeDeductsBalance,
                'requires_attachment' => $this->leaveTypeRequiresAttachment,
                'is_active' => $this->leaveTypeIsActive,
            ]);
            $action = 'leave_type.created';
        }

        $auditService->log(action: $action, auditable: $lt, newValues: ['name' => $lt->name]);
        $this->showLeaveTypeModal = false;
        $this->feedbackMessage = "Jenis cuti {$lt->name} berhasil disimpan!";
    }

    // --- GEOFENCE ACTIONS ---
    public function openCreateGeofence(): void
    {
        $this->reset(['editingGeofenceId', 'geofenceName']);
        $this->geofenceLat = -6.2088;
        $this->geofenceLng = 106.8456;
        $this->geofenceRadius = 150;
        $this->geofenceIsActive = true;
        $this->showGeofenceModal = true;
        $this->dispatch('init-geofence-map', [
            'lat' => $this->geofenceLat,
            'lng' => $this->geofenceLng,
            'radius' => $this->geofenceRadius,
        ]);
    }

    public function editGeofence(int|array $id): void
    {
        $id = is_array($id) ? (int) ($id['id'] ?? reset($id)) : (int) $id;
        $geo = GeofenceLocation::findOrFail($id);
        $this->editingGeofenceId = $geo->id;
        $this->geofenceName = $geo->name;
        $this->geofenceLat = (float) $geo->latitude;
        $this->geofenceLng = (float) $geo->longitude;
        $this->geofenceRadius = (int) $geo->radius_meters;
        $this->geofenceIsActive = (bool) $geo->is_active;
        $this->showGeofenceModal = true;
        $this->dispatch('init-geofence-map', [
            'lat' => $this->geofenceLat,
            'lng' => $this->geofenceLng,
            'radius' => $this->geofenceRadius,
        ]);
    }

    public function saveGeofence(AuditService $auditService): void
    {
        $this->validate([
            'geofenceName' => 'required|string|max:100',
            'geofenceLat' => 'required|numeric|between:-90,90',
            'geofenceLng' => 'required|numeric|between:-180,180',
            'geofenceRadius' => 'required|integer|min:10|max:5000',
        ]);

        if ($this->editingGeofenceId) {
            $geo = GeofenceLocation::findOrFail($this->editingGeofenceId);
            $geo->update([
                'name' => $this->geofenceName,
                'latitude' => $this->geofenceLat,
                'longitude' => $this->geofenceLng,
                'radius_meters' => $this->geofenceRadius,
                'is_active' => $this->geofenceIsActive,
            ]);
            $action = 'geofence.updated';
        } else {
            $geo = GeofenceLocation::create([
                'name' => $this->geofenceName,
                'latitude' => $this->geofenceLat,
                'longitude' => $this->geofenceLng,
                'radius_meters' => $this->geofenceRadius,
                'is_active' => $this->geofenceIsActive,
            ]);
            $action = 'geofence.created';
        }

        $auditService->log(action: $action, auditable: $geo, newValues: ['name' => $geo->name]);
        $this->showGeofenceModal = false;
        $this->feedbackMessage = "Titik lokasi geofence {$geo->name} berhasil disimpan!";
    }

    // --- PAYROLL PERIOD ACTIONS ---
    public function openCreatePeriod(): void
    {
        $this->reset(['editingPeriodId', 'periodName', 'periodStartDate', 'periodEndDate', 'periodCutoffDate']);
        $this->periodIsClosed = false;
        $this->showPeriodModal = true;
    }

    public function editPeriod(int $id): void
    {
        $p = PayrollPeriod::findOrFail($id);
        $this->editingPeriodId = $p->id;
        $this->periodName = $p->name;
        $this->periodStartDate = $p->start_date->toDateString();
        $this->periodEndDate = $p->end_date->toDateString();
        $this->periodCutoffDate = $p->cutoff_date ? $p->cutoff_date->toDateString() : '';
        $this->periodIsClosed = $p->is_closed;
        $this->showPeriodModal = true;
    }

    public function savePeriod(AuditService $auditService): void
    {
        $this->validate([
            'periodName' => 'required|string|max:100',
            'periodStartDate' => 'required|date',
            'periodEndDate' => 'required|date|after_or_equal:periodStartDate',
            'periodCutoffDate' => 'nullable|date',
        ]);

        if ($this->editingPeriodId) {
            $p = PayrollPeriod::findOrFail($this->editingPeriodId);
            $p->update([
                'name' => $this->periodName,
                'start_date' => $this->periodStartDate,
                'end_date' => $this->periodEndDate,
                'cutoff_date' => $this->periodCutoffDate ?: null,
                'is_closed' => $this->periodIsClosed,
            ]);
            $action = 'payroll_period.updated';
        } else {
            $p = PayrollPeriod::create([
                'name' => $this->periodName,
                'start_date' => $this->periodStartDate,
                'end_date' => $this->periodEndDate,
                'cutoff_date' => $this->periodCutoffDate ?: null,
                'is_closed' => $this->periodIsClosed,
            ]);
            $action = 'payroll_period.created';
        }

        $auditService->log(action: $action, auditable: $p, newValues: ['name' => $p->name]);
        $this->showPeriodModal = false;
        $this->feedbackMessage = "Periode payroll {$p->name} berhasil disimpan!";
    }

    // --- BPJS RULE ACTIONS ---
    public function editBpjs(int $id): void
    {
        $rule = BpjsRule::findOrFail($id);
        $this->editingBpjsId = $rule->id;
        $this->bpjsProgramName = $rule->program_name;
        $this->bpjsEmployeeRate = (float) $rule->employee_rate;
        $this->bpjsEmployerRate = (float) $rule->employer_rate;
        $this->bpjsWageCeiling = $rule->wage_ceiling ? (float) $rule->wage_ceiling : null;
        $this->bpjsIsActive = $rule->is_active;
        $this->showBpjsModal = true;
    }

    public function saveBpjs(AuditService $auditService): void
    {
        $this->validate([
            'bpjsEmployeeRate' => 'required|numeric|between:0,1',
            'bpjsEmployerRate' => 'required|numeric|between:0,1',
            'bpjsWageCeiling' => 'nullable|numeric|min:0',
        ]);

        $rule = BpjsRule::findOrFail($this->editingBpjsId);
        $rule->update([
            'employee_rate' => $this->bpjsEmployeeRate,
            'employer_rate' => $this->bpjsEmployerRate,
            'wage_ceiling' => $this->bpjsWageCeiling,
            'is_active' => $this->bpjsIsActive,
        ]);

        $auditService->log(
            action: 'bpjs_rule.updated',
            auditable: $rule,
            newValues: ['program' => $rule->program_name, 'employee_rate' => $rule->employee_rate]
        );

        $this->showBpjsModal = false;
        $this->feedbackMessage = "Tarif BPJS {$rule->program_name} berhasil diperbarui!";
    }

    public function render()
    {
        $departments = Department::orderBy('name')->get();

        $units = Unit::with('department')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $positions = Position::with('unit')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderBy('title')
            ->paginate(15);

        $shifts = Shift::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('start_time')
            ->paginate(15);

        $leaveTypes = LeaveType::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $geofences = GeofenceLocation::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $periods = PayrollPeriod::orderByDesc('start_date')->paginate(15);

        $bpjsRules = BpjsRule::orderBy('program_name')->get();

        $allActiveGeofences = GeofenceLocation::where('is_active', true)->get();

        return view('livewire.master.master-index', [
            'departments' => $departments,
            'units' => $units,
            'positions' => $positions,
            'shifts' => $shifts,
            'leaveTypes' => $leaveTypes,
            'geofences' => $geofences,
            'allActiveGeofences' => $allActiveGeofences,
            'periods' => $periods,
            'bpjsRules' => $bpjsRules,
        ]);
    }
}
