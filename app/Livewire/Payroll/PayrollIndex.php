<?php

namespace App\Livewire\Payroll;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\Audit\AuditService;
use App\Services\Payroll\PayrollCalculationService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manajemen Penggajian (Payroll) - RS Juwita')]
class PayrollIndex extends Component
{
    use WithPagination;

    public ?int $selectedRunId = null;

    public string $searchEmployee = '';

    // Calculate Modal State
    public bool $showCalculateModal = false;

    #[Rule(['required', 'exists:payroll_periods,id'])]
    public ?int $selectedPeriodId = null;

    // Item Detail Modal
    public bool $showItemDetailModal = false;

    public ?int $viewingItemId = null;

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', PayrollRun::class);

        // Select the latest run by default if exists
        $latest = PayrollRun::orderByDesc('run_date')->first();
        if ($latest) {
            $this->selectedRunId = $latest->id;
        }

        $openPeriod = PayrollPeriod::where('is_closed', false)->latest('start_date')->first();
        if ($openPeriod) {
            $this->selectedPeriodId = $openPeriod->id;
        }
    }

    public function selectRun(int $runId): void
    {
        $this->selectedRunId = $runId;
        $this->resetPage();
    }

    public function openCalculateModal(): void
    {
        $this->authorize('calculate', PayrollRun::class);
        $this->showCalculateModal = true;
    }

    public function calculatePayroll(PayrollCalculationService $calculationService, AuditService $auditService): void
    {
        $this->authorize('calculate', PayrollRun::class);
        $this->validate();

        $period = PayrollPeriod::findOrFail($this->selectedPeriodId);

        // Check if an existing run is already finalized for this period
        $finalizedRun = PayrollRun::where('payroll_period_id', $period->id)
            ->whereIn('status', ['finalized', 'paid'])
            ->first();

        if ($finalizedRun) {
            $this->errorMessage = 'Periode ini sudah memiliki penggajian berstatus final dan terkunci.';
            $this->showCalculateModal = false;

            return;
        }

        // Find or create draft/calculated run for this period
        $run = PayrollRun::firstOrCreate(
            ['payroll_period_id' => $period->id, 'status' => 'draft'],
            [
                'run_date' => Carbon::today()->toDateString(),
                'processed_by' => Auth::id(),
                'total_gross_pay' => 0.0,
                'total_deductions' => 0.0,
                'total_net_pay' => 0.0,
            ]
        );

        // Clean any existing items in draft/calculated run before recalculating
        if ($run->status === 'draft' || $run->status === 'calculated') {
            $run->items()->delete();
        }

        $activeEmployees = Employee::active()->with(['currentSalary', 'primaryBankAccount'])->get();
        $calculationService->calculateRun($run, $activeEmployees);

        $auditService->log(
            action: 'payroll.calculated',
            auditable: $run,
            newValues: ['period_id' => $period->id, 'employees_count' => $activeEmployees->count()]
        );

        $this->selectedRunId = $run->id;
        $this->showCalculateModal = false;
        $this->feedbackMessage = "Simulasi penggajian untuk periode {$period->name} berhasil dikalkulasi!";
    }

    public function finalizeRun(AuditService $auditService): void
    {
        $run = PayrollRun::findOrFail($this->selectedRunId);
        $this->authorize('finalize', $run);

        try {
            $run->finalize(Auth::user());

            $auditService->log(
                action: 'payroll.finalized',
                auditable: $run,
                newValues: ['total_net_pay' => $run->total_net_pay]
            );

            $this->feedbackMessage = 'Penggajian berhasil difinalisasi dan dikunci! Data kini bersifat permanen (immutable).';
        } catch (DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function viewItemDetail(int $itemId): void
    {
        $item = PayrollItem::with(['employee.unit', 'employee.position'])->findOrFail($itemId);
        $this->authorize('view', $item);

        $this->viewingItemId = $itemId;
        $this->showItemDetailModal = true;
    }

    public function render()
    {
        $runs = PayrollRun::with('period')->orderByDesc('run_date')->get();
        $periods = PayrollPeriod::where('is_closed', false)->orderByDesc('start_date')->get();

        $selectedRun = $this->selectedRunId ? PayrollRun::with(['period', 'processedBy', 'finalizedBy'])->find($this->selectedRunId) : null;

        $itemsQuery = PayrollItem::with(['employee.unit', 'employee.position'])
            ->where('payroll_run_id', $this->selectedRunId);

        if ($this->searchEmployee !== '') {
            $searchTerm = '%'.strtolower($this->searchEmployee).'%';
            $itemsQuery->whereHas('employee', function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(full_name) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(nip) LIKE ?', [$searchTerm]);
            });
        }

        $items = $itemsQuery->orderBy('id')->paginate(15);

        $viewingItem = $this->viewingItemId ? PayrollItem::with(['employee.unit', 'employee.position'])->find($this->viewingItemId) : null;

        return view('livewire.payroll.payroll-index', [
            'runs' => $runs,
            'periods' => $periods,
            'selectedRun' => $selectedRun,
            'items' => $items,
            'viewingItem' => $viewingItem,
        ]);
    }
}
