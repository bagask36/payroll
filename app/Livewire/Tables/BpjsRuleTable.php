<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\BpjsRule;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class BpjsRuleTable extends PowerGridComponent
{
    public string $tableName = 'bpjs_rules_table';

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
        return BpjsRule::query()->orderBy('program_name');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('program_name_display', fn (BpjsRule $row) => '<span class="font-semibold text-zinc-900 dark:text-zinc-100 uppercase">BPJS '.strtoupper(e($row->program_name)).'</span>')
            ->add('employee_rate_display', fn (BpjsRule $row) => '<span class="font-mono text-xs font-semibold text-rose-600 dark:text-rose-400">'.number_format(((float) $row->employee_rate) * 100, 2).'%</span>')
            ->add('employer_rate_display', fn (BpjsRule $row) => '<span class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400">'.number_format(((float) $row->employer_rate) * 100, 2).'%</span>')
            ->add('wage_ceiling_display', function (BpjsRule $row) {
                if ($row->wage_ceiling) {
                    return '<span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">Rp '.number_format((float) $row->wage_ceiling, 0, ',', '.').'</span>';
                }

                return '<span class="text-zinc-400 text-xs">Tanpa Batas</span>';
            })
            ->add('status_badge', function (BpjsRule $row) {
                if ($row->is_active) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Program Jaminan Sosial', 'program_name_display', 'program_name')
                ->searchable()
                ->sortable(),

            Column::make('Tarif Pegawai', 'employee_rate_display', 'employee_rate')
                ->sortable(),

            Column::make('Tarif RS (Pemberi Kerja)', 'employer_rate_display', 'employer_rate')
                ->sortable(),

            Column::make('Plafon Maksimal Upah', 'wage_ceiling_display', 'wage_ceiling')
                ->sortable(),

            Column::make('Status', 'status_badge', 'is_active')
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function actions(BpjsRule $row): array
    {
        return [
            Button::add('edit')
                ->slot('Ubah Tarif')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editBpjs', ['id' => $row->id]),
        ];
    }
}
