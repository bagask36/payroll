<?php

namespace App\Livewire\Master;

use App\Models\Pph21Parameter;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Data Master: Parameter PPh 21 - RS Permata Bunda')]
class Pph21ParameterIndex extends Component
{
    public string $activeTab = 'ptkp'; // ptkp, tarif_pasal_17

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $paramCategory = 'ptkp';

    public string $paramCode = '';

    public string $paramName = '';

    public ?float $paramRate = null;

    public float|int|string|null $paramThresholdMin = null;

    public float|int|string|null $paramThresholdMax = null;

    public float|int|string|null $paramAmount = null;

    public string $effectiveFrom = '2024-01-01';

    public bool $paramIsActive = true;

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasPermission('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke master data dibatasi khusus Administrator HR dan Super Admin.');
        }
    }

    #[On('editPph21Param')]
    public function edit(int $id): void
    {
        $param = Pph21Parameter::findOrFail($id);
        $this->editingId = $param->id;
        $this->paramCategory = $param->category;
        $this->paramCode = $param->code;
        $this->paramName = $param->name;
        $this->paramRate = $param->rate ? ((float) $param->rate * 100) : null;
        $this->paramThresholdMin = $param->threshold_min ? (float) $param->threshold_min : null;
        $this->paramThresholdMax = $param->threshold_max ? (float) $param->threshold_max : null;
        $this->paramAmount = $param->amount ? (float) $param->amount : null;
        $this->effectiveFrom = $param->effective_from->format('Y-m-d');
        $this->paramIsActive = $param->is_active;
        $this->showModal = true;
    }

    public function save(AuditService $auditService): void
    {
        if (is_string($this->paramAmount)) {
            $cleaned = preg_replace('/[^\d]/', '', $this->paramAmount);
            $this->paramAmount = $cleaned !== '' ? (float) $cleaned : null;
        }
        if (is_string($this->paramThresholdMin)) {
            $cleaned = preg_replace('/[^\d]/', '', $this->paramThresholdMin);
            $this->paramThresholdMin = $cleaned !== '' ? (float) $cleaned : null;
        }
        if (is_string($this->paramThresholdMax)) {
            $cleaned = preg_replace('/[^\d]/', '', $this->paramThresholdMax);
            $this->paramThresholdMax = $cleaned !== '' ? (float) $cleaned : null;
        }

        $this->validate([
            'paramAmount' => $this->paramCategory === 'ptkp' ? 'required|numeric|min:0' : 'nullable|numeric',
            'paramRate' => $this->paramCategory === 'tarif_pasal_17' ? 'required|numeric|min:0|max:100' : 'nullable|numeric',
            'effectiveFrom' => 'required|date',
        ]);

        $param = Pph21Parameter::findOrFail($this->editingId);

        $rateValue = $this->paramRate !== null ? ($this->paramRate / 100) : null;

        $param->update([
            'amount' => $this->paramAmount,
            'rate' => $rateValue,
            'threshold_min' => $this->paramThresholdMin,
            'threshold_max' => $this->paramThresholdMax,
            'effective_from' => $this->effectiveFrom,
            'is_active' => $this->paramIsActive,
        ]);

        $auditService->log(
            action: 'pph21_parameter.updated',
            auditable: $param,
            newValues: ['code' => $param->code, 'amount' => $param->amount, 'rate' => $param->rate]
        );

        $this->showModal = false;
        $this->feedbackMessage = "Parameter PPh 21 '{$param->name}' berhasil diperbarui!";
        $this->dispatch('pg:eventRefresh-pph21_ptkp_table');
        $this->dispatch('pg:eventRefresh-pph21_tarif_table');
    }

    public function render()
    {
        $ptkpList = Pph21Parameter::where('category', 'ptkp')->orderBy('code')->get();
        $tarifPasal17List = Pph21Parameter::where('category', 'tarif_pasal_17')->orderBy('threshold_min')->get();

        return view('livewire.master.pph21-parameter-index', [
            'ptkpList' => $ptkpList,
            'tarifPasal17List' => $tarifPasal17List,
        ]);
    }
}
