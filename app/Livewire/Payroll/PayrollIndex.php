<?php

namespace App\Livewire\Payroll;

use App\Livewire\Concerns\WithDataTable;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Services\Audit\AuditService;
use App\Services\Payroll\PayrollCalculationService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manajemen Penggajian (Payroll) - RS Juwita')]
class PayrollIndex extends Component
{
    use WithDataTable;

    public ?int $selectedRunId = null;

    protected function sortableColumns(): array
    {
        return [
            'employee' => fn ($q, $dir) => $q->orderBy(
                Employee::select('full_name')->whereColumn('employees.id', 'payroll_items.employee_id'),
                $dir
            ),
            'basic_salary' => 'basic_salary',
            'bpjs_kes' => 'bpjs_kes_employee',
            'bpjs_tk' => 'bpjs_tk_employee',
            'net_salary' => 'net_salary',
        ];
    }

    protected function filterProperties(): array
    {
        return [];
    }

    // Calculate Modal State
    public bool $showCalculateModal = false;

    #[Rule(['required', 'exists:payroll_periods,id'])]
    public ?int $selectedPeriodId = null;

    // Off-Cycle THR Modal State
    public bool $showThrModal = false;

    public ?int $selectedThrPeriodId = null;

    public string $thrHolidayName = 'THR Idul Fitri 1447 H';

    public string $thrCutoffDate = '';

    public string $thrTargetReligion = 'all';

    public bool $thrIncludePositional = true;

    // Item Detail Modal
    public bool $showItemDetailModal = false;

    public ?int $viewingItemId = null;

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(?int $selectedRunId = null): void
    {
        $this->authorize('viewAny', PayrollRun::class);

        if ($selectedRunId) {
            $this->selectedRunId = $selectedRunId;
        } else {
            $latest = PayrollRun::orderByDesc('run_date')->first();
            if ($latest) {
                $this->selectedRunId = $latest->id;
            }
        }

        $openPeriod = PayrollPeriod::where('is_closed', false)->latest('start_date')->first();
        if ($openPeriod) {
            $this->selectedPeriodId = $openPeriod->id;
            $this->selectedThrPeriodId = $openPeriod->id;
        }
        $this->thrCutoffDate = Carbon::today()->toDateString();
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

    public function openThrModal(): void
    {
        $this->authorize('calculate', PayrollRun::class);
        $this->showThrModal = true;
        $this->thrCutoffDate = Carbon::today()->toDateString();
        if (! $this->selectedThrPeriodId && $this->selectedPeriodId) {
            $this->selectedThrPeriodId = $this->selectedPeriodId;
        }
    }

    public function calculateThr(\App\Services\Payroll\ThrCalculationService $thrService, AuditService $auditService): void
    {
        $this->authorize('calculate', PayrollRun::class);
        $this->validate([
            'selectedThrPeriodId' => ['required', 'exists:payroll_periods,id'],
            'thrHolidayName' => ['required', 'string', 'max:100'],
            'thrCutoffDate' => ['required', 'date'],
            'thrTargetReligion' => ['required', 'in:all,islam,kristen,katolik,hindu,buddha,khonghucu'],
        ], [
            'selectedThrPeriodId.required' => 'Periode penggajian wajib dipilih.',
            'thrHolidayName.required' => 'Nama event Hari Raya wajib diisi.',
            'thrCutoffDate.required' => 'Tanggal acuan masa kerja wajib diisi.',
        ]);

        $period = PayrollPeriod::findOrFail($this->selectedThrPeriodId);

        // Check if there is already a finalized THR run for this period
        $finalizedRun = PayrollRun::where('payroll_period_id', $period->id)
            ->where('type', 'thr')
            ->whereIn('status', ['finalized', 'paid'])
            ->first();

        if ($finalizedRun) {
            $this->errorMessage = 'Periode ini sudah memiliki batch THR berstatus final dan terkunci.';
            $this->showThrModal = false;

            return;
        }

        // Find or create draft/calculated THR run
        $run = PayrollRun::firstOrCreate(
            ['payroll_period_id' => $period->id, 'type' => 'thr', 'status' => 'draft'],
            [
                'run_date' => $this->thrCutoffDate,
                'processed_by' => Auth::id(),
                'total_gross_pay' => 0.0,
                'total_deductions' => 0.0,
                'total_net_pay' => 0.0,
            ]
        );

        if ($run->status === 'draft' || $run->status === 'calculated') {
            $run->items()->delete();
        }

        $activeEmployees = Employee::active()->with(['currentSalary', 'primaryBankAccount', 'position', 'activeSalaryComponents.component', 'employments'])->get();

        $thrService->calculateThrRun(
            run: $run,
            employees: $activeEmployees,
            cutoffDate: $this->thrCutoffDate,
            options: [
                'holiday_name' => $this->thrHolidayName,
                'target_religion' => $this->thrTargetReligion === 'all' ? null : $this->thrTargetReligion,
                'include_positional_allowance' => $this->thrIncludePositional,
            ]
        );

        $auditService->log(
            action: 'payroll.thr_calculated',
            auditable: $run,
            newValues: [
                'period_id' => $period->id,
                'holiday_name' => $this->thrHolidayName,
                'target_religion' => $this->thrTargetReligion,
                'processed_count' => $run->metadata['processed_count'] ?? 0,
            ]
        );

        $this->selectedRunId = $run->id;
        $this->showThrModal = false;
        $this->feedbackMessage = "Kalkulasi THR ({$this->thrHolidayName}) berhasil diproses untuk ".($run->metadata['processed_count'] ?? 0).' pegawai!';
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

    public function exportBankDisbursement(AuditService $auditService)
    {
        $run = PayrollRun::with(['period', 'items.employee'])->findOrFail($this->selectedRunId);
        $this->authorize('export', PayrollRun::class);

        $periodName = str_replace(' ', '_', $run->period?->name ?? 'Payroll');
        $filename = "Disbursement_{$periodName}_".date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($run) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No_Urut', 'Nomor_Rekening', 'Nama_Penerima', 'Nominal_Transfer', 'Bank_Tujuan', 'Keterangan']);

            $counter = 1;
            foreach ($run->items as $item) {
                fputcsv($file, [
                    $counter++,
                    $item->bank_account_number,
                    $item->employee?->full_name,
                    number_format((float) $item->net_salary, 2, '.', ''),
                    $item->bank_name ?? 'BANK LAIN',
                    $run->isThr() ? 'THR '.($run->metadata['holiday_name'] ?? 'Keagamaan') : "Gaji {$run->period?->name}",
                ]);
            }

            fclose($file);
        };

        $auditService->log(
            action: 'payroll.exported',
            auditable: $run,
            newValues: ['items_count' => $run->items->count(), 'total_amount' => $run->total_net_pay]
        );

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    #[On('view-item-detail')]
    public function viewItemDetail(int $itemId): void
    {
        $item = PayrollItem::with(['employee.unit', 'employee.position', 'components'])->findOrFail($itemId);
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

        $searchTerm = trim($this->search);
        if ($searchTerm !== '') {
            $like = '%'.strtolower($searchTerm).'%';
            $itemsQuery->whereHas('employee', function ($q) use ($like) {
                $q->whereRaw('LOWER(full_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(nip) LIKE ?', [$like]);
            });
        }

        $this->applySorting($itemsQuery, 'id', 'asc');

        $items = $itemsQuery->paginate($this->perPageValue());

        $viewingItem = $this->viewingItemId ? PayrollItem::with(['employee.unit', 'employee.position', 'components'])->find($this->viewingItemId) : null;

        return view('livewire.payroll.payroll-index', [
            'runs' => $runs,
            'periods' => $periods,
            'selectedRun' => $selectedRun,
            'items' => $items,
            'viewingItem' => $viewingItem,
        ]);
    }
}
