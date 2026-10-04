<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ShiftTable extends PowerGridComponent
{
    public string $tableName = 'shifts_table';

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
        return Shift::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code', fn (Shift $s) => '<span class="font-mono font-semibold text-xs text-zinc-800 dark:text-zinc-200">'.e($s->code).'</span>')
            ->add('name', fn (Shift $s) => '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($s->name).'</span>')
            ->add('hours', function (Shift $s) {
                $start = substr((string) $s->start_time, 0, 5);
                $end = substr((string) $s->end_time, 0, 5);

                return '<span class="font-mono text-xs font-semibold text-emerald-700 dark:text-emerald-400">'.e($start).' - '.e($end).' WIB</span>';
            })
            ->add('overnight_badge', function (Shift $s) {
                if ($s->is_overnight) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800">Shift Malam (Lintas Hari)</span>';
                }

                return '<span class="text-zinc-400 text-xs">Reguler</span>';
            })
            ->add('status_badge', function (Shift $s) {
                if ($s->is_active) {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Kode', 'code', 'shifts.code')
                ->searchable()
                ->sortable(),

            Column::make('Nama Shift', 'name', 'shifts.name')
                ->searchable()
                ->sortable(),

            Column::make('Jam Kerja Operasional', 'hours'),

            Column::make('Tipe Shift', 'overnight_badge'),

            Column::make('Status', 'status_badge', 'shifts.is_active')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function actions(Shift $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editShift', ['id' => $row->id]),
        ];
    }
}
