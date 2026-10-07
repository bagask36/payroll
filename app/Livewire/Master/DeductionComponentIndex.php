<?php

namespace App\Livewire\Master;

use App\Models\SalaryComponent;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Data Master: Komponen Potongan - RS Permata Bunda')]
class DeductionComponentIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public bool $isFixed = true;

    public ?float $defaultAmount = 0.00;

    public string $description = '';

    public bool $isActive = true;

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

    public function openCreate(): void
    {
        $this->reset(['editingId', 'code', 'name', 'description']);
        $this->isFixed = true;
        $this->defaultAmount = 0.00;
        $this->isActive = true;
        $this->showModal = true;
    }

    #[On('editComponent')]
    public function edit(int $id): void
    {
        $component = SalaryComponent::where('type', 'deduction')->findOrFail($id);
        $this->editingId = $component->id;
        $this->code = $component->code;
        $this->name = $component->name;
        $this->isFixed = $component->is_fixed;
        $this->defaultAmount = (float) $component->default_amount;
        $this->description = $component->description ?? '';
        $this->isActive = $component->is_active;
        $this->showModal = true;
    }

    public function save(AuditService $auditService): void
    {
        if (is_string($this->defaultAmount)) {
            $cleaned = preg_replace('/[^\d]/', '', $this->defaultAmount);
            $this->defaultAmount = $cleaned !== '' ? (float) $cleaned : null;
        }

        $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('salary_components', 'code')->ignore($this->editingId)],
            'name' => 'required|string|max:100',
            'defaultAmount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $data = [
            'code' => strtoupper(trim($this->code)),
            'name' => trim($this->name),
            'type' => 'deduction',
            'is_taxable' => false,
            'is_fixed' => $this->isFixed,
            'default_amount' => $this->defaultAmount,
            'description' => $this->description ? trim($this->description) : null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            $component = SalaryComponent::findOrFail($this->editingId);
            $component->update($data);

            $auditService->log(
                action: 'salary_component.updated',
                auditable: $component,
                newValues: ['name' => $component->name, 'type' => 'deduction']
            );

            $this->feedbackMessage = "Komponen potongan '{$component->name}' berhasil diperbarui!";
        } else {
            $component = SalaryComponent::create($data);

            $auditService->log(
                action: 'salary_component.created',
                auditable: $component,
                newValues: ['name' => $component->name, 'type' => 'deduction']
            );

            $this->feedbackMessage = "Komponen potongan '{$component->name}' berhasil ditambahkan!";
        }

        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-deduction_components_table');
    }

    public function toggleStatus(int $id, AuditService $auditService): void
    {
        $component = SalaryComponent::where('type', 'deduction')->findOrFail($id);
        $component->update(['is_active' => ! $component->is_active]);

        $auditService->log(
            action: 'salary_component.status_toggled',
            auditable: $component,
            newValues: ['is_active' => $component->is_active]
        );

        $statusStr = $component->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->feedbackMessage = "Komponen '{$component->name}' berhasil {$statusStr}.";
    }

    public function render()
    {
        $query = SalaryComponent::where('type', 'deduction');

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'ilike', '%' . $this->search . '%')
                    ->orWhere('code', 'ilike', '%' . $this->search . '%')
                    ->orWhere('description', 'ilike', '%' . $this->search . '%');
            });
        }

        $components = $query->orderBy('name')->paginate(10);

        return view('livewire.master.deduction-component-index', [
            'components' => $components,
        ]);
    }
}
