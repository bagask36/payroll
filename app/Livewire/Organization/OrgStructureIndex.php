<?php

namespace App\Livewire\Organization;

use App\Models\Department;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Struktur Organisasi - RS Juwita')]
class OrgStructureIndex extends Component
{
    public ?int $selectedDepartmentId = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->can('org.view')) {
            abort(403, 'Akses ke struktur organisasi ditolak.');
        }

        $firstDept = Department::first();
        if ($firstDept) {
            $this->selectedDepartmentId = $firstDept->id;
        }
    }

    public function selectDepartment(int $id): void
    {
        $this->selectedDepartmentId = $id;
    }

    public function render()
    {
        $departments = Department::withCount('units')->get();
        $selectedDept = Department::with(['units.positions'])->find($this->selectedDepartmentId);

        $totalUnits = Unit::count();
        $totalPositions = Position::count();

        return view('livewire.organization.org-structure-index', [
            'departments' => $departments,
            'selectedDept' => $selectedDept,
            'totalUnits' => $totalUnits,
            'totalPositions' => $totalPositions,
        ]);
    }
}
