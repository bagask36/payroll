<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class UnitTable extends PowerGridComponent
{
    public string $tableName = 'units_table';

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
        return Unit::query()
            ->leftJoin('departments', 'units.department_id', '=', 'departments.id')
            ->select([
                'units.*',
                'departments.name as department_name',
            ]);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code', fn (Unit $unit) => '<span class="font-mono font-semibold text-xs text-zinc-800 dark:text-zinc-200">'.e($unit->code).'</span>')
            ->add('name', fn (Unit $unit) => '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($unit->name).'</span>')
            ->add('department_name', fn (Unit $unit) => '<span class="text-zinc-600 dark:text-zinc-400 text-xs">'.e($unit->department?->name ?? $unit->department_name ?? '-').'</span>')
            ->add('status_badge', function (Unit $unit) {
                if ($unit->is_active) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Kode Unit', 'code', 'units.code')
                ->searchable()
                ->sortable(),

            Column::make('Nama Unit Kerja / Ruangan', 'name', 'units.name')
                ->searchable()
                ->sortable(),

            Column::make('Departemen Induk', 'department_name', 'departments.name')
                ->searchable()
                ->sortable(),

            Column::make('Status', 'status_badge', 'units.is_active')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function actions(Unit $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editUnit', ['id' => $row->id]),
        ];
    }
}
