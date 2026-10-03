<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\PayrollItem;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class Pph21ReportTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'pph21_reports_table';

    public ?int $periodId = null;

    public string $status = 'all';

    public string $taxStatus = 'all';

    #[On('pg:filterChanged')]
    public function applyFilter(array $filters): void
    {
        $this->periodId = ! empty($filters['periodId']) ? (int) $filters['periodId'] : null;
        $this->status = $filters['status'] ?? 'all';
        $this->taxStatus = $filters['taxStatus'] ?? 'all';
        $this->resetPage();
    }

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('laporan-pph21-peserta-rs-juwita-'.now()->format('Y-m-d'))
                ->type(Exportable::TYPE_CSV),
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage(perPage: 10, perPageValues: [10, 25, 50, 100])
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $query = PayrollItem::query()
            ->join('employees', 'payroll_items.employee_id', '=', 'employees.id')
            ->join('payroll_runs', 'payroll_items.payroll_run_id', '=', 'payroll_runs.id')
            ->join('payroll_periods', 'payroll_runs.payroll_period_id', '=', 'payroll_periods.id')
            ->leftJoin('units', 'employees.unit_id', '=', 'units.id')
            ->select([
                'payroll_items.*',
                'employees.nip as employee_nip',
                'employees.full_name as employee_name',
                'employees.npwp_number as employee_npwp',
                'employees.marital_status as employee_marital',
                'employees.tax_dependents as employee_dependents',
                'units.name as unit_name',
                'payroll_periods.name as period_name',
                'payroll_runs.status as run_status',
            ]);

        if ($this->periodId) {
            $query->where('payroll_runs.payroll_period_id', $this->periodId);
        }

        if ($this->status !== 'all') {
            $query->where('payroll_runs.status', $this->status);
        }

        if ($this->taxStatus === 'taxable') {
            $query->where('payroll_items.pph21_tax', '>', 0);
        } elseif ($this->taxStatus === 'non_taxable') {
            $query->where('payroll_items.pph21_tax', '<=', 0);
        }

        return $query->latest('payroll_items.id');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('person_display', function (PayrollItem $item) {
                $name = $item->employee_name ?? $item->employee?->full_name ?? '-';
                $nip = $item->employee_nip ?? $item->employee?->nip ?? '-';

                return '<div><div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($name).'</div><div class="text-xs font-mono text-zinc-400">'.e($nip).'</div></div>';
            })
            ->add('unit_name', fn (PayrollItem $item) => '<span class="text-xs text-zinc-700 dark:text-zinc-300">'.e($item->unit_name ?? '-').'</span>')
            ->add('npwp_display', function (PayrollItem $item) {
                $npwp = $item->employee_npwp ?? $item->employee?->npwp_number;
                if ($npwp) {
                    return '<span class="font-mono text-xs text-zinc-800 dark:text-zinc-200">'.e($npwp).'</span>';
                }

                return '<span class="text-xs text-zinc-400 italic">Non-NPWP</span>';
            })
            ->add('ptkp_badge', function (PayrollItem $item) {
                $marital = strtolower((string) ($item->employee_marital ?? $item->employee?->marital_status));
                $prefix = $marital === 'married' ? 'K' : 'TK';
                $dep = min(3, max(0, (int) ($item->employee_dependents ?? $item->employee?->tax_dependents ?? 0)));
                $code = "{$prefix}/{$dep}";

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700">'.e($code).'</span>';
            })
            ->add('basic_salary_formatted', fn (PayrollItem $item) => '<span class="font-mono text-xs text-zinc-800 dark:text-zinc-200">Rp '.number_format((float) $item->basic_salary, 0, ',', '.').'</span>')
            ->add('allowances_formatted', fn (PayrollItem $item) => '<span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">Rp '.number_format((float) $item->allowances, 0, ',', '.').'</span>')
            ->add('gross_pay_formatted', function (PayrollItem $item) {
                $gross = (float) $item->basic_salary + (float) $item->allowances;

                return '<span class="font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">Rp '.number_format($gross, 0, ',', '.').'</span>';
            })
            ->add('pph21_badge', function (PayrollItem $item) {
                $tax = (float) $item->pph21_tax;
                if ($tax > 0) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">Rp '.number_format($tax, 0, ',', '.').'</span>';
                }

                return '<span class="text-xs font-mono text-zinc-400">Rp 0 (Nihil)</span>';
            })
            ->add('net_salary_formatted', fn (PayrollItem $item) => '<span class="font-mono text-xs font-semibold text-emerald-600 dark:text-emerald-400">Rp '.number_format((float) $item->net_salary, 0, ',', '.').'</span>')
            ->add('period_name', fn (PayrollItem $item) => '<span class="text-xs text-zinc-700 dark:text-zinc-300">'.e($item->period_name ?? '-').'</span>')
            ->add('status_badge', function (PayrollItem $item) {
                $status = $item->run_status ?? 'draft';
                if ($status === 'finalized') {
                    return '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-2xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300">Final</span>';
                }
                if ($status === 'paid') {
                    return '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-2xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300">Paid</span>';
                }

                return '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-2xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300">Calculated</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Pegawai', 'person_display', 'employees.full_name')
                ->searchable()
                ->sortable(),

            Column::make('Unit Kerja', 'unit_name', 'units.name')
                ->searchable()
                ->sortable(),

            Column::make('NPWP', 'npwp_display', 'employees.npwp_number')
                ->searchable(),

            Column::make('PTKP', 'ptkp_badge', 'employees.marital_status')
                ->sortable(),

            Column::make('Gaji Pokok', 'basic_salary_formatted', 'payroll_items.basic_salary')
                ->sortable(),

            Column::make('Tunjangan', 'allowances_formatted', 'payroll_items.allowances')
                ->sortable(),

            Column::make('Total Bruto', 'gross_pay_formatted')
                ->sortable(),

            Column::make('PPh 21 Dipotong', 'pph21_badge', 'payroll_items.pph21_tax')
                ->sortable(),

            Column::make('Gaji Bersih (THP)', 'net_salary_formatted', 'payroll_items.net_salary')
                ->sortable(),

            Column::make('Periode', 'period_name', 'payroll_periods.name')
                ->sortable(),

            Column::make('Status', 'status_badge', 'payroll_runs.status')
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function actions(PayrollItem $row): array
    {
        return [
            Button::add('view')
                ->slot('Slip Gaji')
                ->route('payroll.payslips.print', ['id' => $row->id])
                ->attributes(['target' => '_blank'])
                ->class('inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer'),
        ];
    }
}
