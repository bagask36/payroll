<?php

namespace App\Livewire\Report;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Laporan Payroll & Gaji - RS Permata Bunda')]
class PayrollReportIndex extends Component
{
    use WithPagination;

    public ?int $periodFilter = null;

    public string $statusFilter = 'all';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('payroll.view_all') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_manager') && ! $user->hasRole('payroll') && ! $user->hasRole('finance'))) {
            abort(403, 'Akses ke laporan payroll dibatasi.');
        }
    }

    public function updatedPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $query = PayrollRun::with(['period', 'processedBy']);

        if ($this->periodFilter) {
            $query->where('payroll_period_id', $this->periodFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $runs = $query->orderBy('run_date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-payroll-rs-juwita.csv"',
        ];

        $callback = function () use ($runs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No Batch', 'Periode', 'Tanggal Run', 'Status', 'Total Gross (Bruto)', 'Total Potongan', 'Total Net (Take Home Pay)', 'Diproses Oleh']);

            foreach ($runs as $r) {
                fputcsv($file, [
                    substr($r->uuid, 0, 8),
                    $r->period->name ?? '-',
                    $r->run_date->format('d/m/Y'),
                    $r->status,
                    $r->total_gross_pay,
                    $r->total_deductions,
                    $r->total_net_pay,
                    $r->processedBy?->name ?? 'Admin',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = PayrollRun::with(['period', 'processedBy', 'items']);

        if ($this->periodFilter) {
            $query->where('payroll_period_id', $this->periodFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $runs = $query->orderBy('run_date', 'desc')->paginate(10);
        $periods = PayrollPeriod::orderBy('start_date', 'desc')->get();

        $totalDisbursed = PayrollRun::where('status', 'finalized')->sum('total_net_pay');
        $totalGrossAll = PayrollRun::sum('total_gross_pay');
        $totalDeductionsAll = PayrollRun::sum('total_deductions');

        return view('livewire.report.payroll-report-index', [
            'runs' => $runs,
            'periods' => $periods,
            'totalDisbursed' => $totalDisbursed,
            'totalGrossAll' => $totalGrossAll,
            'totalDeductionsAll' => $totalDeductionsAll,
        ]);
    }
}
