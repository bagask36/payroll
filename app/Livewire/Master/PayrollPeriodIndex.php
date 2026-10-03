<?php

namespace App\Livewire\Master;

use App\Models\PayrollPeriod;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Data Master: Periode Payroll & Cut-off - RS Juwita')]
class PayrollPeriodIndex extends Component
{
    use WithPagination;

    public bool $showPeriodModal = false;

    public ?int $editingPeriodId = null;

    public string $periodName = '';

    public string $periodStartDate = '';

    public string $periodEndDate = '';

    public ?string $periodCutoffDate = null;

    public bool $periodIsClosed = false;

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasPermission('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke master data dibatasi khusus Administrator HR dan Super Admin.');
        }
    }

    public function openCreatePeriod(): void
    {
        $this->reset(['editingPeriodId', 'periodName', 'periodStartDate', 'periodEndDate', 'periodCutoffDate']);
        $this->periodIsClosed = false;
        $this->showPeriodModal = true;
    }

    #[On('editPeriod')]
    public function editPeriod(int $id): void
    {
        $period = PayrollPeriod::findOrFail($id);
        $this->editingPeriodId = $period->id;
        $this->periodName = $period->name;
        $this->periodStartDate = $period->start_date ? $period->start_date->toDateString() : '';
        $this->periodEndDate = $period->end_date ? $period->end_date->toDateString() : '';
        $this->periodCutoffDate = $period->cutoff_date ? $period->cutoff_date->toDateString() : null;
        $this->periodIsClosed = $period->is_closed;
        $this->showPeriodModal = true;
    }

    public function savePeriod(AuditService $auditService): void
    {
        $this->validate([
            'periodName' => 'required|string|max:100',
            'periodStartDate' => 'required|date',
            'periodEndDate' => 'required|date|after_or_equal:periodStartDate',
            'periodCutoffDate' => 'nullable|date',
        ]);

        if ($this->editingPeriodId) {
            $period = PayrollPeriod::findOrFail($this->editingPeriodId);
            $period->update([
                'name' => $this->periodName,
                'start_date' => $this->periodStartDate,
                'end_date' => $this->periodEndDate,
                'cutoff_date' => $this->periodCutoffDate,
                'is_closed' => $this->periodIsClosed,
            ]);
            $action = 'payroll_period.updated';
        } else {
            $period = PayrollPeriod::create([
                'name' => $this->periodName,
                'start_date' => $this->periodStartDate,
                'end_date' => $this->periodEndDate,
                'cutoff_date' => $this->periodCutoffDate,
                'is_closed' => $this->periodIsClosed,
            ]);
            $action = 'payroll_period.created';
        }

        $auditService->log(action: $action, auditable: $period, newValues: ['name' => $period->name]);
        $this->showPeriodModal = false;
        $this->feedbackMessage = "{$period->name} berhasil disimpan!";
        $this->dispatch('pg:eventRefresh-payroll_periods_table');
    }

    public function render()
    {
        $periods = PayrollPeriod::orderByDesc('start_date')->paginate(15);

        return view('livewire.master.payroll-period-index', [
            'periods' => $periods,
        ]);
    }
}
