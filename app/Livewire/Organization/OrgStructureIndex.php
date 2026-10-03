<?php

namespace App\Livewire\Organization;

use App\Models\Department;
use App\Models\Position;
use App\Models\Unit;
use App\Services\Audit\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Struktur Organisasi - RS Juwita')]
class OrgStructureIndex extends Component
{
    public string $search = '';
    public ?string $feedbackMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.view')) {
            abort(403, 'Akses ke struktur organisasi ditolak.');
        }
    }

    /**
     * Move a position into another unit via drag and drop.
     */
    public function movePosition(int $positionId, int $targetUnitId, AuditService $auditService): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah struktur organisasi.');
        }

        $position = Position::findOrFail($positionId);
        $targetUnit = Unit::findOrFail($targetUnitId);

        if ($position->unit_id === $targetUnit->id) {
            return;
        }

        $oldUnit = $position->unit;
        $oldUnitId = $position->unit_id;

        $position->unit_id = $targetUnit->id;
        $position->save();

        $auditService->log(
            action: 'org.position_moved',
            auditable: $position,
            oldValues: ['unit_id' => $oldUnitId, 'unit_name' => $oldUnit?->name],
            newValues: ['unit_id' => $targetUnit->id, 'unit_name' => $targetUnit->name]
        );

        $this->errorMessage = null;
        $this->feedbackMessage = "Jabatan '{$position->title}' berhasil dipindahkan ke Unit '{$targetUnit->name}'.";
    }

    /**
     * Move a unit into another department via drag and drop.
     */
    public function moveUnit(int $unitId, int $targetDepartmentId, AuditService $auditService): void
    {
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

    /**
     * Move a department under another parent department (or root) via drag and drop.
     */
    public function moveDepartment(int $departmentId, ?int $targetParentId, AuditService $auditService): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.update')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah struktur organisasi.');
        }

        $dept = Department::findOrFail($departmentId);

        // Cannot parent to self
        if ($targetParentId !== null && $targetParentId === $dept->id) {
            $this->errorMessage = "Departemen tidak dapat menjadi induk bagi dirinya sendiri.";
            return;
        }

        // Check circular reference
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

    /**
     * Check if a candidate parent is actually a descendant of departmentId.
     */
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
                'children' => fn ($q) => $q->with([
                    'units.positions',
                    'children.units.positions',
                ]),
                'units.positions',
            ])
            ->get();

        // Also check if any standalone departments exist without valid parent
        $allDeptIds = Department::pluck('id')->toArray();
        $standaloneDepartments = Department::whereNotNull('parent_id')
            ->whereNotIn('parent_id', $allDeptIds)
            ->with(['units.positions'])
            ->get();

        $totalDepartments = Department::count();
        $totalUnits = Unit::count();
        $totalPositions = Position::count();
        $canManage = Auth::user()?->can('org.update') ?? false;

        return view('livewire.organization.org-structure-index', [
            'rootDepartments' => $rootDepartments,
            'standaloneDepartments' => $standaloneDepartments,
            'totalDepartments' => $totalDepartments,
            'totalUnits' => $totalUnits,
            'totalPositions' => $totalPositions,
            'canManage' => $canManage,
        ]);
    }
}
