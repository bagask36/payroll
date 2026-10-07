<?php

namespace App\Livewire\Master;

use App\Models\Department;
use App\Models\Unit;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Data Master: Unit Kerja - RS Permata Bunda')]
class UnitIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showUnitModal = false;

    public ?int $editingUnitId = null;

    public string $unitCode = '';

    public string $unitName = '';

    public ?int $unitDepartmentId = null;

    public bool $unitIsActive = true;

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasPermission('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke master data dibatasi khusus Administrator HR dan Super Admin.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateUnit(): void
    {
        $this->reset(['editingUnitId', 'unitCode', 'unitName', 'unitDepartmentId']);
        $this->unitIsActive = true;
        $this->showUnitModal = true;
    }

    #[On('editUnit')]
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
            'unitCode' => ['required', 'string', 'max:20', Rule::unique('units', 'code')->ignore($this->editingUnitId)],
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
        $this->dispatch('pg:eventRefresh-units_table');
    }

    public function render()
    {
        $departments = Department::orderBy('name')->get();

        $units = Unit::with('department')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.master.unit-index', [
            'departments' => $departments,
            'units' => $units,
        ]);
    }
}
