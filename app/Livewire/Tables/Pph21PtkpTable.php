<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Pph21Parameter;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class Pph21PtkpTable extends PowerGridComponent
{
    public string $tableName = 'pph21_ptkp_table';

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
        return Pph21Parameter::query()
            ->where('category', 'ptkp')
            ->orderBy('code');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code_badge', fn (Pph21Parameter $row) => '<span class="font-mono font-bold text-xs text-emerald-700 dark:text-emerald-400">'.e($row->code).'</span>')
            ->add('name', fn (Pph21Parameter $row) => '<span class="font-medium text-zinc-900 dark:text-zinc-100 text-sm">'.e($row->name).'</span>')
            ->add('amount_annual', fn (Pph21Parameter $row) => '<span class="font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">Rp '.number_format((float) $row->amount, 0, ',', '.').'</span>')
            ->add('amount_monthly', fn (Pph21Parameter $row) => '<span class="font-mono text-xs text-zinc-500">Rp '.number_format(((float) $row->amount) / 12, 0, ',', '.').'</span>')
            ->add('status_badge', function (Pph21Parameter $row) {
                if ($row->is_active) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Kode Status', 'code_badge', 'code')
                ->searchable()
                ->sortable(),

            Column::make('Deskripsi Status Pernikahan', 'name')
                ->searchable()
                ->sortable(),

            Column::make('Nilai PTKP Tahunan', 'amount_annual', 'amount')
                ->sortable(),

            Column::make('PTKP Bulanan (Acuan)', 'amount_monthly'),

            Column::make('Status', 'status_badge', 'is_active')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function actions(Pph21Parameter $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editPph21Param', ['id' => $row->id]),
        ];
    }
}
