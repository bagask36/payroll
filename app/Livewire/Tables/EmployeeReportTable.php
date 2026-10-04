<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class EmployeeReportTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'employee_reports_table';

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('laporan-kepegawaian-rs-juwita-'.now()->format('Y-m-d'))
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
        return Employee::query()
            ->leftJoin('units', 'employees.unit_id', '=', 'units.id')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->select([
                'employees.*',
                'units.name as unit_name',
                'positions.title as position_title',
            ])
            ->with(['currentEmployment', 'activeWarningLetters.warningLetterType']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nip', fn (Employee $emp) => '<span class="font-mono text-xs font-semibold text-zinc-700 dark:text-zinc-300">'.e($emp->nip).'</span>')
            ->add('full_name', fn (Employee $emp) => '<span class="font-medium text-zinc-900 dark:text-zinc-100">'.e($emp->full_name).'</span>')
            ->add('unit_name', fn (Employee $emp) => '<span class="text-zinc-600 dark:text-zinc-400 text-xs">'.e($emp->unit?->name ?? $emp->unit_name ?? '-').'</span>')
            ->add('position_title', fn (Employee $emp) => '<span class="text-zinc-800 dark:text-zinc-200 text-xs">'.e($emp->position?->title ?? $emp->position_title ?? '-').'</span>')
            ->add('status_badge', function (Employee $emp) {
                if ($emp->status === 'active') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">'.e(ucfirst($emp->status)).'</span>';
            })
            ->add('contract_badge', function (Employee $emp) {
                $contract = $emp->currentEmployment?->contract_type;
                if ($contract === 'permanent') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">Tetap (PKWTT)</span>';
                }
                if ($contract === 'contract') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Kontrak (PKWT)</span>';
                }

                return '<span class="text-zinc-400 text-xs">-</span>';
            })
            ->add('join_date_display', fn (Employee $emp) => '<span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">'.($emp->currentEmployment?->join_date ? $emp->currentEmployment->join_date->format('d/m/Y') : '-').'</span>')
            ->add('disciplinary_badge', function (Employee $emp) {
                $activeSp = $emp->activeWarningLetters->first();
                if ($activeSp) {
                    $typeName = e($activeSp->warningLetterType?->name ?? 'SP');
                    $validUntil = $activeSp->valid_until ? $activeSp->valid_until->format('d/m/Y') : '-';

                    return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800" title="Berlaku s.d. '.$validUntil.'">SP Aktif: '.$typeName.'</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-normal text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">Tertib</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('NIP', 'nip', 'employees.nip')
                ->searchable()
                ->sortable(),

            Column::make('Nama Pegawai', 'full_name', 'employees.full_name')
                ->searchable()
                ->sortable(),

            Column::make('Unit Kerja', 'unit_name', 'units.name')
                ->searchable()
                ->sortable(),

            Column::make('Jabatan / Formasi', 'position_title', 'positions.title')
                ->searchable()
                ->sortable(),

            Column::make('Status', 'status_badge', 'employees.status')
                ->sortable(),

            Column::make('Ikatan Kerja', 'contract_badge'),

            Column::make('Mulai Tugas', 'join_date_display'),

            Column::make('Kedisiplinan', 'disciplinary_badge'),

            Column::action('Aksi'),
        ];
    }

    public function actions(Employee $row): array
    {
        return [
            Button::add('view')
                ->slot('Detail')
                ->route('employees.show', ['id' => $row->id])
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer'),
        ];
    }
}
