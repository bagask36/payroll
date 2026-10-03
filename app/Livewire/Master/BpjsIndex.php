<?php

namespace App\Livewire\Master;

use App\Models\BpjsRule;
use App\Services\Audit\AuditService;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Data Master: Regulasi Tarif BPJS - RS Juwita')]
class BpjsIndex extends Component
{
    public bool $showBpjsModal = false;

    public ?int $editingBpjsId = null;

    public string $bpjsProgramName = '';

    public float $bpjsEmployeeRate = 0.01;

    public float $bpjsEmployerRate = 0.04;

    public ?float $bpjsWageCeiling = null;

    public bool $bpjsIsActive = true;

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasPermission('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke master data dibatasi khusus Administrator HR dan Super Admin.');
        }
    }

    #[On('editBpjs')]
    public function editBpjs(int $id): void
    {
        $rule = BpjsRule::findOrFail($id);
        $this->editingBpjsId = $rule->id;
        $this->bpjsProgramName = $rule->program_name;
        $this->bpjsEmployeeRate = (float) $rule->employee_rate;
        $this->bpjsEmployerRate = (float) $rule->employer_rate;
        $this->bpjsWageCeiling = $rule->wage_ceiling ? (float) $rule->wage_ceiling : null;
        $this->bpjsIsActive = $rule->is_active;
        $this->showBpjsModal = true;
    }

    public function saveBpjs(AuditService $auditService): void
    {
        $this->validate([
            'bpjsEmployeeRate' => 'required|numeric|min:0|max:1',
            'bpjsEmployerRate' => 'required|numeric|min:0|max:1',
            'bpjsWageCeiling' => 'nullable|numeric|min:0',
        ]);

        $rule = BpjsRule::findOrFail($this->editingBpjsId);
        $rule->update([
            'employee_rate' => $this->bpjsEmployeeRate,
            'employer_rate' => $this->bpjsEmployerRate,
            'wage_ceiling' => $this->bpjsWageCeiling,
            'is_active' => $this->bpjsIsActive,
        ]);

        $auditService->log(
            action: 'bpjs_rule.updated',
            auditable: $rule,
            newValues: ['program' => $rule->program_name, 'employee_rate' => $rule->employee_rate]
        );

        $this->showBpjsModal = false;
        $this->feedbackMessage = "Tarif BPJS {$rule->program_name} berhasil diperbarui!";
        $this->dispatch('pg:eventRefresh-bpjs_rules_table');
    }

    public function render()
    {
        $bpjsRules = BpjsRule::orderBy('program_name')->get();

        return view('livewire.master.bpjs-index', [
            'bpjsRules' => $bpjsRules,
        ]);
    }
}
