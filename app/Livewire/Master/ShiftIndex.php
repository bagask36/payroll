<?php

namespace App\Livewire\Master;

use App\Models\Shift;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Data Master: Shift Kerja & Jam Dinas - RS Permata Bunda')]
class ShiftIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showShiftModal = false;

    public ?int $editingShiftId = null;

    public string $shiftCode = '';

    public string $shiftName = '';

    public string $shiftStartTime = '07:00';

    public string $shiftEndTime = '14:00';

    public bool $shiftIsOvernight = false;

    public int $shiftLateTolerance = 15;

    public bool $shiftIsActive = true;

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

    public function openCreateShift(): void
    {
        $this->reset(['editingShiftId', 'shiftCode', 'shiftName']);
        $this->shiftStartTime = '07:00';
        $this->shiftEndTime = '14:00';
        $this->shiftIsOvernight = false;
        $this->shiftLateTolerance = 15;
        $this->shiftIsActive = true;
        $this->showShiftModal = true;
    }

    #[On('editShift')]
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
            'shiftCode' => ['required', 'string', 'max:20', Rule::unique('shifts', 'code')->ignore($this->editingShiftId)],
            'shiftName' => 'required|string|max:100',
            'shiftStartTime' => 'required',
            'shiftEndTime' => 'required',
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
        $this->dispatch('pg:eventRefresh-shifts_table');
    }

    public function render()
    {
        $shifts = Shift::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('start_time')
            ->paginate(15);

        return view('livewire.master.shift-index', [
            'shifts' => $shifts,
        ]);
    }
}
