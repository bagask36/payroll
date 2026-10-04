<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PositionTable extends PowerGridComponent
{
    public string $tableName = 'positions_table';

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
        return Position::query()
            ->leftJoin('units', 'positions.unit_id', '=', 'units.id')
            ->leftJoin('positions as supervisors', 'positions.reports_to_id', '=', 'supervisors.id')
            ->select([
                'positions.*',
                'units.name as unit_name',
                'supervisors.title as supervisor_title',
            ]);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('title', fn (Position $p) => '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($p->title).'</span>')
            ->add('unit_name', fn (Position $p) => '<span class="text-zinc-600 dark:text-zinc-400 text-xs">'.e($p->unit?->name ?? $p->unit_name ?? '-').'</span>')
            ->add('supervisor', fn (Position $p) => ($p->supervisorPosition?->title ?? $p->supervisor_title)
                ? '<span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">'.e($p->supervisorPosition?->title ?? $p->supervisor_title).'</span>'
                : '<span class="text-zinc-400 text-xs italic">-</span>')
            ->add('grade', fn (Position $p) => $p->grade_level
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">'.e($p->grade_level).'</span>'
                : '<span class="text-zinc-400 text-xs">-</span>')
            ->add('default_basic_salary_formatted', fn (Position $p) => $p->default_basic_salary
                ? '<span class="font-mono text-xs font-medium text-zinc-900 dark:text-zinc-100">Rp '.number_format((float) $p->default_basic_salary, 0, ',', '.').'</span>'
                : '<span class="text-zinc-400 text-xs italic">-</span>')
            ->add('default_positional_allowance_formatted', fn (Position $p) => $p->default_positional_allowance
                ? '<span class="font-mono text-xs font-medium text-emerald-700 dark:text-emerald-400">Rp '.number_format((float) $p->default_positional_allowance, 0, ',', '.').'</span>'
                : '<span class="text-zinc-400 text-xs italic">-</span>')
            ->add('default_contract_number', fn (Position $p) => $p->default_contract_number
                ? '<div class="flex items-center gap-1 font-mono text-xs text-zinc-800 dark:text-zinc-200"><span>'.e($p->default_contract_number).'</span>'.($p->document_id ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-sans font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">Berkas SK</span>' : '').'</div>'
                : '<span class="text-zinc-400 text-xs italic">-</span>');
    }

    public function columns(): array
    {
        return [
            Column::make('Standar Jabatan', 'title', 'positions.title')
                ->searchable()
                ->sortable(),

            Column::make('Atasan Langsung', 'supervisor', 'supervisors.title')
                ->searchable()
                ->sortable(),

            Column::make('Unit Kerja Terkait', 'unit_name', 'units.name')
                ->searchable()
                ->sortable(),

            Column::make('Golongan / Jenjang', 'grade', 'positions.grade_level')
                ->searchable()
                ->sortable(),

            Column::make('SK Formasi Jabatan', 'default_contract_number', 'positions.default_contract_number')
                ->searchable()
                ->sortable(),

            Column::make('Std. Gaji Pokok', 'default_basic_salary_formatted', 'positions.default_basic_salary')
                ->sortable(),

            Column::make('Std. Tunj. Jabatan', 'default_positional_allowance_formatted', 'positions.default_positional_allowance')
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function actions(Position $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('editPosition', ['id' => $row->id]),
        ];
    }
}
