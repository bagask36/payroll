<?php

namespace App\Livewire\Master;

use App\Models\Position;
use App\Models\Unit;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Data Master: Standar Jabatan - RS Juwita')]
class PositionIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showPositionModal = false;

    public ?int $editingPositionId = null;

    public string $positionTitle = '';

    public ?int $positionUnitId = null;

    public ?int $positionReportsToId = null;

    public string $positionGradeLevel = 'III/a';

    public ?string $positionDefaultBasicSalary = '';

    public ?string $positionDefaultPositionalAllowance = '';

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

    public function openCreatePosition(): void
    {
        $this->reset(['editingPositionId', 'positionTitle', 'positionUnitId', 'positionReportsToId', 'positionDefaultBasicSalary', 'positionDefaultPositionalAllowance']);
        $this->positionGradeLevel = 'III/a';
        $this->showPositionModal = true;
    }

    #[On('editPosition')]
    public function editPosition(int $id): void
    {
        $pos = Position::findOrFail($id);
        $this->editingPositionId = $pos->id;
        $this->positionTitle = $pos->title;
        $this->positionUnitId = $pos->unit_id;
        $this->positionReportsToId = $pos->reports_to_id;
        $this->positionGradeLevel = $pos->grade_level ?? 'III/a';
        $this->positionDefaultBasicSalary = $pos->default_basic_salary !== null ? (string) (float) $pos->default_basic_salary : '';
        $this->positionDefaultPositionalAllowance = $pos->default_positional_allowance !== null ? (string) (float) $pos->default_positional_allowance : '';
        $this->showPositionModal = true;
    }

    public function savePosition(AuditService $auditService): void
    {
        $this->validate([
            'positionTitle' => 'required|string|max:100',
            'positionUnitId' => 'nullable|exists:units,id',
            'positionReportsToId' => 'nullable|exists:positions,id|different:editingPositionId',
            'positionGradeLevel' => 'nullable|string|max:20',
            'positionDefaultBasicSalary' => 'nullable|numeric|min:0',
            'positionDefaultPositionalAllowance' => 'nullable|numeric|min:0',
        ]);

        $payload = [
            'title' => $this->positionTitle,
            'unit_id' => $this->positionUnitId,
            'reports_to_id' => $this->positionReportsToId,
            'grade_level' => $this->positionGradeLevel,
            'default_basic_salary' => $this->positionDefaultBasicSalary !== '' && $this->positionDefaultBasicSalary !== null ? $this->positionDefaultBasicSalary : null,
            'default_positional_allowance' => $this->positionDefaultPositionalAllowance !== '' && $this->positionDefaultPositionalAllowance !== null ? $this->positionDefaultPositionalAllowance : null,
        ];

        if ($this->editingPositionId) {
            $pos = Position::findOrFail($this->editingPositionId);
            $pos->update($payload);
            $action = 'position.updated';
        } else {
            $pos = Position::create($payload);
            $action = 'position.created';
        }

        $auditService->log(action: $action, auditable: $pos, newValues: ['title' => $pos->title]);
        $this->showPositionModal = false;
        $this->feedbackMessage = "Jabatan {$pos->title} berhasil disimpan!";
        $this->dispatch('pg:eventRefresh-positions_table');
    }

    public function render()
    {
        $units = Unit::orderBy('name')->get();

        $allPositions = Position::when($this->editingPositionId, fn ($q) => $q->where('id', '!=', $this->editingPositionId))
            ->orderBy('title')
            ->get();

        $positions = Position::with(['unit', 'supervisorPosition'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderBy('title')
            ->paginate(15);

        return view('livewire.master.position-index', [
            'units' => $units,
            'allPositions' => $allPositions,
            'positions' => $positions,
        ]);
    }
}
