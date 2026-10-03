<?php

namespace App\Livewire\Report;

use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Laporan PPh 21 Pegawai - RS Juwita')]
class Pph21ReportIndex extends Component
{
    public ?int $periodFilter = null;

    public string $statusFilter = 'all';

    public string $taxStatusFilter = 'all'; // all, taxable, non_taxable

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('payroll.view_all') && ! $user->can('statutory.view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_manager') && ! $user->hasRole('payroll') && ! $user->hasRole('finance'))) {
            abort(403, 'Akses ke laporan PPh 21 dibatasi khusus pengelola SDM dan Keuangan.');
        }
    }

    public function updatedPeriodFilter(): void
    {
        $this->dispatch('pg:filterChanged', [
            'periodId' => $this->periodFilter,
            'status' => $this->statusFilter,
            'taxStatus' => $this->taxStatusFilter,
        ]);
    }

    public function updatedStatusFilter(): void
    {
        $this->dispatch('pg:filterChanged', [
            'periodId' => $this->periodFilter,
            'status' => $this->statusFilter,
            'taxStatus' => $this->taxStatusFilter,
        ]);
    }

    public function updatedTaxStatusFilter(): void
    {
        $this->dispatch('pg:filterChanged', [
            'periodId' => $this->periodFilter,
            'status' => $this->statusFilter,
            'taxStatus' => $this->taxStatusFilter,
        ]);
    }

    public function exportCsv()
    {
        $query = PayrollItem::query()
            ->join('employees', 'payroll_items.employee_id', '=', 'employees.id')
            ->join('payroll_runs', 'payroll_items.payroll_run_id', '=', 'payroll_runs.id')
            ->join('payroll_periods', 'payroll_runs.payroll_period_id', '=', 'payroll_periods.id')
            ->leftJoin('units', 'employees.unit_id', '=', 'units.id')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->select([
                'employees.nip',
                'employees.full_name',
                'employees.npwp_number',
                'employees.marital_status',
                'employees.tax_dependents',
                'units.name as unit_name',
                'positions.title as position_title',
                'payroll_periods.name as period_name',
                'payroll_runs.status as run_status',
                'payroll_items.basic_salary',
                'payroll_items.allowances',
                'payroll_items.bpjs_kes_employee',
                'payroll_items.bpjs_tk_employee',
                'payroll_items.pph21_tax',
                'payroll_items.net_salary',
            ]);

        if ($this->periodFilter) {
            $query->where('payroll_runs.payroll_period_id', $this->periodFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('payroll_runs.status', $this->statusFilter);
        }

        if ($this->taxStatusFilter === 'taxable') {
            $query->where('payroll_items.pph21_tax', '>', 0);
        } elseif ($this->taxStatusFilter === 'non_taxable') {
            $query->where('payroll_items.pph21_tax', '<=', 0);
        }

        $items = $query->orderBy('employees.full_name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-pph21-rs-juwita-'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'NIP',
                'Nama Pegawai',
                'Unit Kerja',
                'Jabatan',
                'NPWP',
                'Status PTKP',
                'Gaji Pokok',
                'Tunjangan',
                'Total Bruto',
                'BPJS Kes Pegawai',
                'BPJS TK Pegawai',
                'PPh 21 Dipotong',
                'Take Home Pay',
                'Periode',
                'Status Batch',
            ]);

            foreach ($items as $item) {
                $prefix = strtolower((string) $item->marital_status) === 'married' ? 'K' : 'TK';
                $ptkp = $prefix.'/'.min(3, max(0, (int) $item->tax_dependents));
                $gross = (float) $item->basic_salary + (float) $item->allowances;

                fputcsv($file, [
                    $item->nip,
                    $item->full_name,
                    $item->unit_name ?? '-',
                    $item->position_title ?? '-',
                    $item->npwp_number ?? 'Non-NPWP',
                    $ptkp,
                    $item->basic_salary,
                    $item->allowances,
                    $gross,
                    $item->bpjs_kes_employee,
                    $item->bpjs_tk_employee,
                    $item->pph21_tax,
                    $item->net_salary,
                    $item->period_name,
                    $item->run_status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = PayrollItem::query()
            ->join('payroll_runs', 'payroll_items.payroll_run_id', '=', 'payroll_runs.id');

        if ($this->periodFilter) {
            $query->where('payroll_runs.payroll_period_id', $this->periodFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('payroll_runs.status', $this->statusFilter);
        }

        if ($this->taxStatusFilter === 'taxable') {
            $query->where('payroll_items.pph21_tax', '>', 0);
        } elseif ($this->taxStatusFilter === 'non_taxable') {
            $query->where('payroll_items.pph21_tax', '<=', 0);
        }

        $totalPph21 = (float) (clone $query)->sum('payroll_items.pph21_tax');
        $totalGross = (float) (clone $query)->selectRaw('SUM(payroll_items.basic_salary + payroll_items.allowances) as total')->value('total');
        $totalParticipants = (int) (clone $query)->count();
        $taxableParticipants = (int) (clone $query)->where('payroll_items.pph21_tax', '>', 0)->count();

        $periods = PayrollPeriod::orderBy('start_date', 'desc')->get();

        return view('livewire.report.pph21-report-index', [
            'periods' => $periods,
            'totalPph21' => $totalPph21,
            'totalGross' => $totalGross,
            'totalParticipants' => $totalParticipants,
            'taxableParticipants' => $taxableParticipants,
        ]);
    }
}
