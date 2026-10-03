<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class PayrollReportTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'payroll_reports_table';

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('laporan-penggajian-rs-juwita-'.now()->format('Y-m-d'))
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
        return PayrollRun::query()
            ->leftJoin('payroll_periods', 'payroll_runs.payroll_period_id', '=', 'payroll_periods.id')
            ->select([
                'payroll_runs.*',
                'payroll_periods.name as period_name',
            ])
            ->latest('payroll_runs.run_date');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('batch_display', fn (PayrollRun $r) => '<span class="font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">BATCH-#'.$r->id.'</span>')
            ->add('period_name', fn (PayrollRun $r) => '<span class="font-medium text-zinc-800 dark:text-zinc-200">'.e($r->period?->name ?? $r->period_name ?? '-').'</span>')
            ->add('run_date_display', fn (PayrollRun $r) => '<span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">'.($r->run_date ? $r->run_date->format('d/m/Y') : '-').'</span>')
            ->add('status_badge', function (PayrollRun $r) {
                if ($r->status === 'finalized') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Final (Terkunci)</span>';
                }
                if ($r->status === 'paid') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">Dibayarkan</span>';
                }
                if ($r->status === 'calculated') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Terkalkulasi (Draft)</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">'.e(ucfirst($r->status)).'</span>';
            })
            ->add('gross_pay_formatted', fn (PayrollRun $r) => '<span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">Rp '.number_format((float) $r->total_gross_pay, 0, ',', '.').'</span>')
            ->add('deductions_formatted', fn (PayrollRun $r) => '<span class="font-mono text-xs font-semibold text-rose-600 dark:text-rose-400">Rp '.number_format((float) $r->total_deductions, 0, ',', '.').'</span>')
            ->add('net_pay_formatted', fn (PayrollRun $r) => '<span class="font-mono text-xs font-semibold text-emerald-600 dark:text-emerald-400">Rp '.number_format((float) $r->total_net_pay, 0, ',', '.').'</span>');
    }

    public function columns(): array
    {
        return [
            Column::make('Nomor Batch', 'batch_display', 'payroll_runs.id')
                ->sortable(),

            Column::make('Periode Penggajian', 'period_name', 'payroll_periods.name')
                ->searchable()
                ->sortable(),

            Column::make('Tanggal Run', 'run_date_display', 'payroll_runs.run_date')
                ->sortable(),

            Column::make('Status', 'status_badge', 'payroll_runs.status')
                ->sortable(),

            Column::make('Total Bruto', 'gross_pay_formatted', 'payroll_runs.total_gross_pay')
                ->sortable(),

            Column::make('Total Potongan', 'deductions_formatted', 'payroll_runs.total_deductions')
                ->sortable(),

            Column::make('Take Home Pay', 'net_pay_formatted', 'payroll_runs.total_net_pay')
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function actions(PayrollRun $row): array
    {
        return [
            Button::add('view')
                ->slot('Lihat Rincian')
                ->route('payroll.index', ['runId' => $row->id])
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer'),
        ];
    }
}
