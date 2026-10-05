<?php

namespace App\Livewire\Master;

use App\Models\WarningLetterType;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Data Master: Tingkat & Jenis Surat Peringatan (SP) - RS Juwita')]
class WarningLetterTypeIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public int $level = 1;

    public int $validityMonths = 6;

    public string $description = '';

    public bool $isActive = true;

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasPermission('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin') && ! $user->hasRole('hr_manager'))) {
            abort(403, 'Akses ke master data dibatasi khusus Administrator HR dan Super Admin.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'code', 'name', 'description']);
        $this->level = 1;
        $this->validityMonths = 6;
        $this->isActive = true;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
        $this->showModal = true;
    }

    #[On('editWarningLetterType')]
    public function edit(int $id): void
    {
        $wlt = WarningLetterType::findOrFail($id);
        $this->editingId = $wlt->id;
        $this->code = $wlt->code;
        $this->name = $wlt->name;
        $this->level = $wlt->level;
        $this->validityMonths = $wlt->validity_months;
        $this->description = $wlt->description ?? '';
        $this->isActive = $wlt->is_active;
        $this->feedbackMessage = null;
        $this->errorMessage = null;
        $this->showModal = true;
    }

    public function save(AuditService $auditService): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('warning_letter_types', 'code')->ignore($this->editingId)],
            'name' => 'required|string|max:150',
            'level' => 'required|integer|min:0|max:10',
            'validityMonths' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($this->editingId) {
            $wlt = WarningLetterType::findOrFail($this->editingId);
            $oldValues = $wlt->toArray();
            $wlt->update([
                'code' => strtoupper(trim($this->code)),
                'name' => trim($this->name),
                'level' => $this->level,
                'validity_months' => $this->validityMonths,
                'description' => trim($this->description) ?: null,
                'is_active' => $this->isActive,
            ]);
            $action = 'warning_letter_type.updated';
            $auditService->log(action: $action, auditable: $wlt, oldValues: $oldValues, newValues: $wlt->toArray());
            $this->feedbackMessage = "Jenis SP \"{$wlt->name}\" berhasil diperbarui!";
        } else {
            $wlt = WarningLetterType::create([
                'code' => strtoupper(trim($this->code)),
                'name' => trim($this->name),
                'level' => $this->level,
                'validity_months' => $this->validityMonths,
                'description' => trim($this->description) ?: null,
                'is_active' => $this->isActive,
            ]);
            $action = 'warning_letter_type.created';
            $auditService->log(action: $action, auditable: $wlt, newValues: $wlt->toArray());
            $this->feedbackMessage = "Jenis SP baru \"{$wlt->name}\" berhasil didaftarkan!";
        }

        $this->showModal = false;
    }

    public function toggleStatus(int $id, AuditService $auditService): void
    {
        $wlt = WarningLetterType::findOrFail($id);
        $wlt->is_active = ! $wlt->is_active;
        $wlt->save();

        $auditService->log(
            action: 'warning_letter_type.status_toggled',
            auditable: $wlt,
            newValues: ['is_active' => $wlt->is_active]
        );

        $this->feedbackMessage = "Status jenis SP \"{$wlt->name}\" berhasil diubah.";
    }

    public function render()
    {
        $warningLetterTypes = WarningLetterType::when($this->search, function ($q) {
            $q->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%");
        })
            ->orderBy('level')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.master.warning-letter-type-index', [
            'warningLetterTypes' => $warningLetterTypes,
        ]);
    }
}
