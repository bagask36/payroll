<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Employee;
use App\Models\PayrollItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class MyPayslipTable extends PowerGridComponent
{
    public string $tableName = 'my_payslips_table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage(perPage: 10, perPageValues: [10, 25, 50])
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $employee = Employee::where('user_id', Auth::id())->first();

        return PayrollItem::query()
            ->join('payroll_runs', 'payroll_items.payroll_run_id', '=', 'payroll_runs.id')
            ->leftJoin('payroll_periods', 'payroll_runs.payroll_period_id', '=', 'payroll_periods.id')
            ->select([
                'payroll_items.*',
                'payroll_runs.run_date as run_date',
                'payroll_runs.status as run_status',
                'payroll_periods.name as period_name',
                'payroll_periods.cutoff_date as period_cutoff_date',
            ])
            ->where('payroll_items.employee_id', $employee?->id)
            ->whereIn('payroll_runs.status', ['calculated', 'finalized', 'paid'])
            ->with(['run.period']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('period_display', function (PayrollItem $row) {
                $periodName = e($row->period_name ?? $row->run?->period?->name ?? '-');
                $cutoff = $row->run?->period?->cutoff_date?->format('d M Y') ?? '-';

                return "
                    <div class=\"font-semibold text-zinc-900 dark:text-zinc-100\">{$periodName}</div>
                    <div class=\"text-xs text-zinc-400\">Cut-off: {$cutoff}</div>
                ";
            })
            ->add('payment_date', fn (PayrollItem $row) => '<span class="font-mono text-xs text-zinc-500">'.e($row->run?->run_date?->format('d/m/Y') ?? '-').'</span>')
            ->add('basic_salary_formatted', fn (PayrollItem $row) => '<span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">Rp '.number_format((float) $row->basic_salary, 0, ',', '.').'</span>')
            ->add('total_deductions_formatted', function (PayrollItem $row) {
                $total = $row->bpjs_kes_employee + $row->bpjs_tk_employee + $row->pph21_tax + $row->deductions;

                return '<span class="font-mono text-xs text-rose-600 dark:text-rose-400">-Rp '.number_format((float) $total, 0, ',', '.').'</span>';
            })
            ->add('net_salary_formatted', fn (PayrollItem $row) => '<span class="font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400">Rp '.number_format((float) $row->net_salary, 0, ',', '.').'</span>')
            ->add('status_badge', function (PayrollItem $row) {
                $status = $row->run_status ?? $row->run?->status;
                if ($status === 'finalized' || $status === 'paid') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Final</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Estimasi</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Periode Penggajian', 'period_display', 'payroll_periods.name')
                ->searchable(),

            Column::make('Tanggal Pembayaran', 'payment_date', 'payroll_runs.run_date')
                ->sortable(),

            Column::make('Gaji Pokok', 'basic_salary_formatted', 'payroll_items.basic_salary')
                ->sortable(),

            Column::make('Total Potongan', 'total_deductions_formatted'),

            Column::make('Gaji Bersih Diterima', 'net_salary_formatted', 'payroll_items.net_salary')
                ->sortable(),

            Column::make('Status', 'status_badge'),

            Column::action('Opsi'),
        ];
    }

    public function actions(PayrollItem $row): array
    {
        return [
            Button::add('viewSlip')
                ->slot('Lihat Slip')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('view-slip-detail', ['itemId' => $row->id]),
        ];
    }
}
