<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PayrollPeriodTable extends PowerGridComponent
{
    public string $tableName = 'payroll_periods_table';

    public function setUp(): array
    {
        return [
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
        return PayrollPeriod::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name', fn (PayrollPeriod $p) => '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($p->name).'</span>')
            ->add('date_range', function (PayrollPeriod $p) {
                $start = $p->start_date ? $p->start_date->format('d/m/Y') : '-';
                $end = $p->end_date ? $p->end_date->format('d/m/Y') : '-';

                return '<span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">'.e($start).' s/d '.e($end).'</span>';
            })
            ->add('cutoff_display', fn (PayrollPeriod $p) => '<span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">'.($p->cutoff_date ? $p->cutoff_date->format('d/m/Y') : '-').'</span>')
            ->add('status_badge', function (PayrollPeriod $p) {
                if ($p->is_closed) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Tutup Buku (Closed)</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif Berjalan (Open)</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Nama Periode Penggajian', 'name', 'payroll_periods.name')
                ->searchable()
                ->sortable(),

            Column::make('Rentang Tanggal Periode', 'date_range'),

            Column::make('Tanggal Cutoff Presensi', 'cutoff_display'),

            Column::make('Status', 'status_badge', 'payroll_periods.is_closed')
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function actions(PayrollPeriod $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editPeriod', ['id' => $row->id]),
        ];
    }
}
