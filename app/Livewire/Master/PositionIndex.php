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

    public string $positionGradeLevel = 'III/a';

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
        $this->reset(['editingPositionId', 'positionTitle', 'positionUnitId']);
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
        $this->dispatch('pg:eventRefresh-positions_table');
    }

    public function render()
    {
        $units = Unit::orderBy('name')->get();

        $positions = Position::with('unit')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderBy('title')
            ->paginate(15);

        return view('livewire.master.position-index', [
            'units' => $units,
            'positions' => $positions,
        ]);
    }
}
