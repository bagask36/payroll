<?php

namespace App\Livewire\Master;

use App\Models\LeaveType;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Data Master: Jenis Cuti & Kebijakan - RS Permata Bunda')]
class LeaveTypeIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showLeaveTypeModal = false;

    public ?int $editingLeaveTypeId = null;

    public string $leaveTypeCode = '';

    public string $leaveTypeName = '';

    public int $leaveTypeDefaultDays = 12;

    public bool $leaveTypeDeductsBalance = true;

    public bool $leaveTypeRequiresAttachment = false;

    public bool $leaveTypeIsActive = true;

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

    public function openCreateLeaveType(): void
    {
        $this->reset(['editingLeaveTypeId', 'leaveTypeCode', 'leaveTypeName']);
        $this->leaveTypeDefaultDays = 12;
        $this->leaveTypeDeductsBalance = true;
        $this->leaveTypeRequiresAttachment = false;
        $this->leaveTypeIsActive = true;
        $this->showLeaveTypeModal = true;
    }

    #[On('editLeaveType')]
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
            'leaveTypeCode' => ['required', 'string', 'max:20', Rule::unique('leave_types', 'code')->ignore($this->editingLeaveTypeId)],
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
        $this->dispatch('pg:eventRefresh-leave_types_table');
    }

    public function render()
    {
        $leaveTypes = LeaveType::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.master.leave-type-index', [
            'leaveTypes' => $leaveTypes,
        ]);
    }
}
