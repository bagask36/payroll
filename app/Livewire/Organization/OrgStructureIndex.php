<?php

namespace App\Livewire\Organization;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Unit;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Struktur Organisasi - RS Permata Bunda')]
class OrgStructureIndex extends Component
{
    public string $search = '';
    public ?string $feedbackMessage = null;
    public ?string $errorMessage = null;

    // --- DEPARTMENT MODAL STATE ---
    public bool $showDepartmentModal = false;
    public ?int $editingDeptId = null;
    public string $deptCode = '';
    public string $deptName = '';
    public ?int $deptParentId = null;
    public bool $deptIsActive = true;

    // --- UNIT MODAL STATE ---
    public bool $showUnitModal = false;
    public ?int $editingUnitId = null;
    public string $unitSource = 'custom'; // 'custom' or 'master'
    public ?int $selectedMasterUnitId = null;
    public string $unitCode = '';
    public string $unitName = '';
    public ?int $unitDepartmentId = null;
    public bool $unitIsActive = true;

    // --- POSITION MODAL STATE ---
    public bool $showPositionModal = false;
    public ?int $editingPositionId = null;
    public string $positionSource = 'master'; // 'master' or 'custom'
    public string $selectedMasterTitle = '';
    public string $positionTitle = '';
    public string $positionPlacementType = 'unit'; // 'unit' or 'department'
    public ?int $positionDepartmentId = null;
    public ?int $positionUnitId = null;
    public string $positionGradeLevel = 'staff';

    // --- DELETE CONFIRMATION STATE ---
    public bool $showDeleteModal = false;
    public string $deleteType = ''; // 'department', 'unit', 'position'
    public ?int $deleteId = null;
    public string $deleteName = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.view')) {
            abort(403, 'Akses ke struktur organisasi ditolak.');
        }
    }

    // ==========================================
    // DEPARTMENT CRUD
    // ==========================================

    public function openCreateDepartment(?int $parentId = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat departemen.');
        }

        $this->reset(['editingDeptId', 'deptCode', 'deptName', 'deptParentId']);
        $this->deptParentId = $parentId;
        $this->deptIsActive = true;
        $this->showDepartmentModal = true;
    }

    public function editDepartment(int $id): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah departemen.');
        }

        $dept = Department::findOrFail($id);
        $this->editingDeptId = $dept->id;
        $this->deptCode = $dept->code;
        $this->deptName = $dept->name;
        $this->deptParentId = $dept->parent_id;
        $this->deptIsActive = $dept->is_active;
        $this->showDepartmentModal = true;
    }

    public function saveDepartment(AuditService $auditService): void
    {
        $user = Auth::user();

        if ($this->editingDeptId) {
            if (! $user || ! $user->can('org.update')) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengubah departemen.');
            }
        } else {
            if (! $user || ! $user->can('org.create')) {
                abort(403, 'Anda tidak memiliki hak akses untuk membuat departemen.');
            }
        }

        $this->validate([
            'deptCode' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($this->editingDeptId)],
            'deptName' => 'required|string|max:100',
            'deptParentId' => 'nullable|exists:departments,id',
        ]);

        if ($this->editingDeptId && $this->deptParentId) {
            if ($this->deptParentId === $this->editingDeptId || $this->isDescendant($this->deptParentId, $this->editingDeptId)) {
                $this->addError('deptParentId', 'Departemen tidak boleh menjadi induk untuk dirinya sendiri atau turunannya.');
                return;
            }
        }

        if ($this->editingDeptId) {
            $dept = Department::findOrFail($this->editingDeptId);
            $oldValues = $dept->only(['code', 'name', 'parent_id', 'is_active']);

            $dept->update([
                'code' => strtoupper($this->deptCode),
                'name' => $this->deptName,
                'parent_id' => $this->deptParentId ?: null,
                'is_active' => $this->deptIsActive,
            ]);

            $auditService->log('department.updated', $dept, $oldValues, $dept->only(['code', 'name', 'parent_id', 'is_active']));
            $this->feedbackMessage = "Departemen '{$dept->name}' berhasil diperbarui.";
        } else {
            $dept = Department::create([
                'code' => strtoupper($this->deptCode),
                'name' => $this->deptName,
                'parent_id' => $this->deptParentId ?: null,
                'is_active' => $this->deptIsActive,
            ]);

            $auditService->log('department.created', $dept, null, $dept->only(['code', 'name', 'parent_id', 'is_active']));
            $this->feedbackMessage = "Departemen baru '{$dept->name}' berhasil ditambahkan.";
        }

        $this->showDepartmentModal = false;
        $this->errorMessage = null;
    }

    // ==========================================
    // UNIT CRUD (With Master Selection or New Entry)
    // ==========================================

    public function openCreateUnit(?int $departmentId = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat unit kerja.');
        }

        $this->reset(['editingUnitId', 'unitCode', 'unitName', 'unitDepartmentId', 'selectedMasterUnitId']);
        $this->unitDepartmentId = $departmentId;
        $this->unitSource = 'master';
        $this->unitIsActive = true;
        $this->showUnitModal = true;
    }

    public function updatedSelectedMasterUnitId(?int $value): void
    {
        if ($value) {
            $unit = Unit::find($value);
            if ($unit) {
                $this->unitCode = $unit->code;
                $this->unitName = $unit->name;
                $this->unitIsActive = $unit->is_active;
            }
        }
    }

    public function editUnit(int $id): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah unit kerja.');
        }

        $unit = Unit::findOrFail($id);
        $this->editingUnitId = $unit->id;
        $this->unitSource = 'custom';
        $this->unitCode = $unit->code;
        $this->unitName = $unit->name;
        $this->unitDepartmentId = $unit->department_id;
        $this->unitIsActive = $unit->is_active;
        $this->showUnitModal = true;
    }

    public function saveUnit(AuditService $auditService): void
    {
        $user = Auth::user();

        if ($this->editingUnitId) {
            if (! $user || ! $user->can('org.update')) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengubah unit kerja.');
            }
        } else {
            if (! $user || ! $user->can('org.create')) {
                abort(403, 'Anda tidak memiliki hak akses untuk membuat unit kerja.');
            }
        }

        // If assigning an existing master unit to this department
        if ($this->unitSource === 'master' && ! $this->editingUnitId) {
            if ($this->selectedMasterUnitId) {
                $this->validate([
                    'selectedMasterUnitId' => 'required|exists:units,id',
                    'unitDepartmentId' => 'required|exists:departments,id',
                ], [
                    'selectedMasterUnitId.required' => 'Silakan pilih unit kerja dari Data Master.',
                    'unitDepartmentId.required' => 'Silakan pilih departemen penempatan.',
                ]);

                $unit = Unit::findOrFail($this->selectedMasterUnitId);
                $oldValues = $unit->only(['code', 'name', 'department_id', 'is_active']);

                $unit->update([
                    'department_id' => $this->unitDepartmentId,
                ]);

                $auditService->log('unit.updated', $unit, $oldValues, $unit->only(['code', 'name', 'department_id', 'is_active']));
                $this->showUnitModal = false;
                $this->errorMessage = null;
                $this->feedbackMessage = "Unit kerja '{$unit->name}' berhasil ditautkan ke Departemen.";
                return;
            } elseif (! $this->unitCode) {
                $this->validate([
                    'selectedMasterUnitId' => 'required|exists:units,id',
                ], [
                    'selectedMasterUnitId.required' => 'Silakan pilih unit kerja dari Data Master.',
                ]);
            }
        }

        $this->validate([
            'unitCode' => ['required', 'string', 'max:20', Rule::unique('units', 'code')->ignore($this->editingUnitId)],
            'unitName' => 'required|string|max:100',
            'unitDepartmentId' => 'required|exists:departments,id',
        ]);

        if ($this->editingUnitId) {
            $unit = Unit::findOrFail($this->editingUnitId);
            $oldValues = $unit->only(['code', 'name', 'department_id', 'is_active']);

            $unit->update([
                'code' => strtoupper($this->unitCode),
                'name' => $this->unitName,
                'department_id' => $this->unitDepartmentId,
                'is_active' => $this->unitIsActive,
            ]);

            $auditService->log('unit.updated', $unit, $oldValues, $unit->only(['code', 'name', 'department_id', 'is_active']));
            $this->feedbackMessage = "Unit kerja '{$unit->name}' berhasil diperbarui.";
        } else {
            $unit = Unit::create([
                'code' => strtoupper($this->unitCode),
                'name' => $this->unitName,
                'department_id' => $this->unitDepartmentId,
                'is_active' => $this->unitIsActive,
            ]);

            $auditService->log('unit.created', $unit, null, $unit->only(['code', 'name', 'department_id', 'is_active']));
            $this->feedbackMessage = "Unit kerja baru '{$unit->name}' berhasil ditambahkan ke Data Master.";
        }

        $this->showUnitModal = false;
        $this->errorMessage = null;
    }

    // ==========================================
    // POSITION CRUD (With Master Selection or New Entry)
    // ==========================================

    public function openCreatePosition(?int $unitId = null, ?int $departmentId = null): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat formasi jabatan.');
        }

        $this->reset(['editingPositionId', 'positionTitle', 'selectedMasterTitle']);
        $this->positionSource = 'master';
        
        if ($departmentId !== null) {
            $this->positionPlacementType = 'department';
            $this->positionDepartmentId = $departmentId;
            $this->positionUnitId = null;
        } else {
            $this->positionPlacementType = 'unit';
            $this->positionUnitId = $unitId;
            $this->positionDepartmentId = null;
        }

        $this->positionGradeLevel = 'staff';
        $this->showPositionModal = true;
    }

    public function updatedSelectedMasterTitle(string $value): void
    {
        if ($value) {
            $this->positionTitle = $value;
            $sample = Position::where('title', $value)->first();
            if ($sample && $sample->grade_level) {
                $this->positionGradeLevel = $sample->grade_level;
            }
        }
    }

    public function editPosition(int $id): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah formasi jabatan.');
        }

        $pos = Position::findOrFail($id);
        $this->editingPositionId = $pos->id;
        $this->positionSource = 'custom';
        $this->positionTitle = $pos->title;
        $this->positionGradeLevel = $pos->grade_level;

        if ($pos->department_id) {
            $this->positionPlacementType = 'department';
            $this->positionDepartmentId = $pos->department_id;
            $this->positionUnitId = null;
        } else {
            $this->positionPlacementType = 'unit';
            $this->positionUnitId = $pos->unit_id;
            $this->positionDepartmentId = null;
        }

        $this->showPositionModal = true;
    }

    public function savePosition(AuditService $auditService): void
    {
        $user = Auth::user();

        if ($this->editingPositionId) {
            if (! $user || ! $user->can('org.update')) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengubah formasi jabatan.');
            }
        } else {
            if (! $user || ! $user->can('org.create')) {
                abort(403, 'Anda tidak memiliki hak akses untuk membuat formasi jabatan.');
            }
        }

        // If choosing from master, ensure positionTitle is taken from selectedMasterTitle if not empty
        if ($this->positionSource === 'master' && ! $this->editingPositionId) {
            if ($this->selectedMasterTitle) {
                $this->positionTitle = $this->selectedMasterTitle;
            } elseif (! $this->positionTitle) {
                $this->validate([
                    'selectedMasterTitle' => 'required|string',
                ], [
                    'selectedMasterTitle.required' => 'Silakan pilih standar jabatan dari Data Master.',
                ]);
            }
        }

        $rules = [
            'positionTitle' => 'required|string|max:100',
            'positionGradeLevel' => 'required|in:director,manager,supervisor,specialist,staff',
            'positionPlacementType' => 'required|in:unit,department',
        ];

        if ($this->positionPlacementType === 'department') {
            $rules['positionDepartmentId'] = 'required|exists:departments,id';
        } else {
            $rules['positionUnitId'] = 'required|exists:units,id';
        }

        $this->validate($rules);

        $departmentId = $this->positionPlacementType === 'department' ? $this->positionDepartmentId : null;
        $unitId = $this->positionPlacementType === 'unit' ? $this->positionUnitId : null;

        if ($this->editingPositionId) {
            $pos = Position::findOrFail($this->editingPositionId);
            $oldValues = $pos->only(['title', 'unit_id', 'department_id', 'grade_level']);

            $pos->update([
                'title' => $this->positionTitle,
                'department_id' => $departmentId,
                'unit_id' => $unitId,
                'grade_level' => $this->positionGradeLevel,
            ]);

            $auditService->log('position.updated', $pos, $oldValues, $pos->only(['title', 'unit_id', 'department_id', 'grade_level']));
            $this->feedbackMessage = "Formasi jabatan '{$pos->title}' berhasil diperbarui.";
        } else {
            $pos = Position::create([
                'title' => $this->positionTitle,
                'department_id' => $departmentId,
                'unit_id' => $unitId,
                'grade_level' => $this->positionGradeLevel,
            ]);

            $auditService->log('position.created', $pos, null, $pos->only(['title', 'unit_id', 'department_id', 'grade_level']));
            $this->feedbackMessage = "Formasi jabatan baru '{$pos->title}' berhasil ditambahkan ke Data Master.";
        }

        $this->showPositionModal = false;
        $this->errorMessage = null;
    }

    // ==========================================
    // DELETE ACTIONS WITH INTEGRITY CHECKS
    // ==========================================

    public function confirmDelete(string $type, int $id, string $name): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus data organisasi.');
        }

        $this->deleteType = $type;
        $this->deleteId = $id;
        $this->deleteName = $name;
        $this->showDeleteModal = true;
    }

    public function executeDelete(AuditService $auditService): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus data organisasi.');
        }

        if (! $this->deleteId || ! $this->deleteType) {
            return;
        }

        if ($this->deleteType === 'department') {
            $dept = Department::findOrFail($this->deleteId);

            if ($dept->children()->exists()) {
                $this->errorMessage = "Tidak dapat menghapus departemen '{$dept->name}' karena masih memiliki sub-departemen di bawahnya.";
                $this->showDeleteModal = false;
                return;
            }

            if ($dept->units()->exists()) {
                $this->errorMessage = "Tidak dapat menghapus departemen '{$dept->name}' karena masih memiliki unit kerja terdaftar.";
                $this->showDeleteModal = false;
                return;
            }

            if ($dept->positions()->exists()) {
                $this->errorMessage = "Tidak dapat menghapus departemen '{$dept->name}' karena masih memiliki formasi jabatan langsung di bawahnya.";
                $this->showDeleteModal = false;
                return;
            }

            $deptName = $dept->name;
            $auditService->log('department.deleted', $dept, $dept->toArray(), null);
            $dept->delete();

            $this->feedbackMessage = "Departemen '{$deptName}' berhasil dihapus.";
        } elseif ($this->deleteType === 'unit') {
            $unit = Unit::findOrFail($this->deleteId);

            if ($unit->positions()->exists()) {
                $this->errorMessage = "Tidak dapat menghapus unit '{$unit->name}' karena masih memiliki formasi jabatan terdaftar.";
                $this->showDeleteModal = false;
                return;
            }

            if (Employee::where('unit_id', $unit->id)->exists()) {
                $this->errorMessage = "Tidak dapat menghapus unit '{$unit->name}' karena masih terikat dengan data pegawai.";
                $this->showDeleteModal = false;
                return;
            }

            $unitName = $unit->name;
            $auditService->log('unit.deleted', $unit, $unit->toArray(), null);
            $unit->delete();

            $this->feedbackMessage = "Unit kerja '{$unitName}' berhasil dihapus.";
        } elseif ($this->deleteType === 'position') {
            $pos = Position::findOrFail($this->deleteId);

            if (Employee::where('position_id', $pos->id)->exists()) {
                $this->errorMessage = "Tidak dapat menghapus formasi jabatan '{$pos->title}' karena masih digunakan oleh data pegawai aktif/tercatat.";
                $this->showDeleteModal = false;
                return;
            }

            $posTitle = $pos->title;
            $auditService->log('position.deleted', $pos, $pos->toArray(), null);
            $pos->delete();

            $this->feedbackMessage = "Formasi jabatan '{$posTitle}' berhasil dihapus.";
        }

        $this->showDeleteModal = false;
        $this->reset(['deleteType', 'deleteId', 'deleteName']);
    }

    // ==========================================
    // DRAG AND DROP REORGANIZATION
    // ==========================================

    public function movePosition(int $positionId, int $targetId, string $targetType = 'unit', ?AuditService $auditService = null): void
    {
        $auditService = $auditService ?? app(AuditService::class);
        $user = Auth::user();
        if (! $user || ! $user->can('org.update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah struktur organisasi.');
        }

        $position = Position::findOrFail($positionId);

        if ($targetType === 'department') {
            $targetDept = Department::findOrFail($targetId);

            if ($position->department_id === $targetDept->id && $position->unit_id === null) {
                return;
            }

            $oldValues = $position->only(['unit_id', 'department_id']);
            $position->department_id = $targetDept->id;
            $position->unit_id = null;
            $position->save();

            $auditService->log(
                action: 'org.position_moved',
                auditable: $position,
                oldValues: $oldValues,
                newValues: ['department_id' => $targetDept->id, 'department_name' => $targetDept->name]
            );

            $this->errorMessage = null;
            $this->feedbackMessage = "Jabatan '{$position->title}' berhasil dipindahkan langsung ke Departemen '{$targetDept->name}'.";
        } else {
            $targetUnit = Unit::findOrFail($targetId);

            if ($position->unit_id === $targetUnit->id) {
                return;
            }

            $oldUnit = $position->unit;
            $oldValues = $position->only(['unit_id', 'department_id']);

            $position->unit_id = $targetUnit->id;
            $position->department_id = null;
            $position->save();

            $auditService->log(
                action: 'org.position_moved',
                auditable: $position,
                oldValues: $oldValues,
                newValues: ['unit_id' => $targetUnit->id, 'unit_name' => $targetUnit->name]
            );

            $this->errorMessage = null;
            $this->feedbackMessage = "Jabatan '{$position->title}' berhasil dipindahkan ke Unit '{$targetUnit->name}'.";
        }
    }

    public function moveUnit(int $unitId, int $targetDepartmentId, ?AuditService $auditService = null): void
    {
        $auditService = $auditService ?? app(AuditService::class);
        $user = Auth::user();
        if (! $user || ! $user->can('org.update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah struktur organisasi.');
        }

        $unit = Unit::findOrFail($unitId);
        $targetDept = Department::findOrFail($targetDepartmentId);

        if ($unit->department_id === $targetDept->id) {
            return;
        }

        $oldDept = $unit->department;
        $oldDeptId = $unit->department_id;

        $unit->department_id = $targetDept->id;
        $unit->save();

        $auditService->log(
            action: 'org.unit_moved',
            auditable: $unit,
            oldValues: ['department_id' => $oldDeptId, 'department_name' => $oldDept?->name],
            newValues: ['department_id' => $targetDept->id, 'department_name' => $targetDept->name]
        );

        $this->errorMessage = null;
        $this->feedbackMessage = "Unit '{$unit->name}' berhasil dipindahkan ke Departemen '{$targetDept->name}'.";
    }

    public function moveDepartment(int $departmentId, ?int $targetParentId, ?AuditService $auditService = null): void
    {
        $auditService = $auditService ?? app(AuditService::class);
        $user = Auth::user();
        if (! $user || ! $user->can('org.update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah struktur organisasi.');
        }

        $dept = Department::findOrFail($departmentId);

        if ($targetParentId !== null && $targetParentId === $dept->id) {
            $this->errorMessage = "Departemen tidak dapat menjadi induk bagi dirinya sendiri.";
            return;
        }

        if ($targetParentId !== null && $this->isDescendant($targetParentId, $dept->id)) {
            $this->errorMessage = "Gagal: Tidak dapat memindahkan departemen ke dalam cabangnya sendiri (siklik).";
            return;
        }

        $oldParentId = $dept->parent_id;
        $targetParent = $targetParentId ? Department::find($targetParentId) : null;

        $dept->parent_id = $targetParentId;
        $dept->save();

        $auditService->log(
            action: 'org.department_moved',
            auditable: $dept,
            oldValues: ['parent_id' => $oldParentId],
            newValues: ['parent_id' => $targetParentId]
        );

        $this->errorMessage = null;
        $targetName = $targetParent ? "Departemen '{$targetParent->name}'" : "Tingkat Utama (Root)";
        $this->feedbackMessage = "Departemen '{$dept->name}' berhasil dipindahkan ke {$targetName}.";
    }

    private function isDescendant(int $candidateParentId, int $departmentId): bool
    {
        if ($candidateParentId === $departmentId) {
            return true;
        }

        $current = Department::find($candidateParentId);
        while ($current && $current->parent_id) {
            if ($current->parent_id === $departmentId) {
                return true;
            }
            $current = Department::find($current->parent_id);
        }

        return false;
    }

    public function render()
    {
        $rootDepartments = Department::whereNull('parent_id')
            ->with([
                'positions',
                'children' => fn ($q) => $q->with([
                    'positions',
                    'units.positions',
                    'children.positions',
                    'children.units.positions',
                ]),
                'units.positions',
            ])
            ->orderBy('name')
            ->get();

        $allDeptIds = Department::pluck('id')->toArray();
        $standaloneDepartments = Department::whereNotNull('parent_id')
            ->whereNotIn('parent_id', $allDeptIds)
            ->with(['positions', 'units.positions'])
            ->orderBy('name')
            ->get();

        $allDepartments = Department::orderBy('name')->get();
        $allUnits = Unit::with('department')->orderBy('name')->get();

        // Unique position titles registered across the master database
        $masterPositionTitles = Position::select('title', 'grade_level')
            ->get()
            ->unique('title')
            ->sortBy('title')
            ->values();

        $totalDepartments = Department::count();
        $totalUnits = Unit::count();
        $totalPositions = Position::count();

        $user = Auth::user();
        $canCreate = $user?->can('org.create') ?? false;
        $canUpdate = $user?->can('org.update') ?? false;
        $canDelete = $user?->can('org.delete') ?? false;

        return view('livewire.organization.org-structure-index', [
            'rootDepartments' => $rootDepartments,
            'standaloneDepartments' => $standaloneDepartments,
            'allDepartments' => $allDepartments,
            'allUnits' => $allUnits,
            'masterPositionTitles' => $masterPositionTitles,
            'totalDepartments' => $totalDepartments,
            'totalUnits' => $totalUnits,
            'totalPositions' => $totalPositions,
            'canCreate' => $canCreate,
            'canUpdate' => $canUpdate,
            'canDelete' => $canDelete,
            'canManage' => $canUpdate,
        ]);
    }
}
