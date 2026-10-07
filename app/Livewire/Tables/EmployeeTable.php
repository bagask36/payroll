<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Livewire\Tables\Concerns\HasSafeFilters;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class EmployeeTable extends PowerGridComponent
{
    use HasSafeFilters;

    public string $tableName = 'employees_table';

    public string $scheduleTypeFilter = 'all';

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
        $query = Employee::query()
            ->leftJoin('units', 'employees.unit_id', '=', 'units.id')
            ->leftJoin('positions', 'employees.position_id', '=', 'positions.id')
            ->select([
                'employees.*',
                'units.name as unit_name',
                'positions.title as position_title',
            ])
            ->with(['currentEmployment']);

        if ($this->scheduleTypeFilter === 'shift') {
            $query->where('employees.schedule_type', 'shift');
        } elseif ($this->scheduleTypeFilter === 'non_shift') {
            $query->where(function ($q) {
                $q->where('employees.schedule_type', 'non_shift')
                    ->orWhereNull('employees.schedule_type');
            });
        }

        return $query;
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('employee_display', function (Employee $emp) {
                $initials = strtoupper(substr($emp->full_name, 0, 2));
                $url = route('employees.show', $emp->id);

                return '
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 font-semibold text-xs shrink-0">
                            '.e($initials).'
                        </div>
                        <div>
                            <a href="'.e($url).'" class="font-medium text-zinc-900 dark:text-zinc-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                                '.e($emp->full_name).'
                            </a>
                            <div class="text-xs font-mono text-zinc-400">'.e($emp->nip).'</div>
                        </div>
                    </div>
                ';
            })
            ->add('full_name')
            ->add('nip')
            ->add('unit_name', fn (Employee $emp) => e($emp->currentEmployment?->unit?->name ?? $emp->unit_name ?? '-'))
            ->add('position_title', fn (Employee $emp) => e($emp->currentEmployment?->position?->title ?? $emp->position_title ?? '-'))
            ->add('contract_badge', function (Employee $emp) {
                $contract = $emp->currentEmployment?->contract_type;
                if ($contract === 'permanent') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Tetap</span>';
                }
                if ($contract === 'contract') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Kontrak</span>';
                }
                if ($contract === 'probation') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">Percobaan</span>';
                }

                return '<span class="text-zinc-400 text-xs">-</span>';
            })
            ->add('schedule_type_badge', function (Employee $emp) {
                $type = $emp->schedule_type ?? 'non_shift';
                if ($type === 'shift') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800">Shift</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800">Non-Shift (Reguler)</span>';
            })
            ->add('phone_number', function (Employee $emp) {
                $phone = e($emp->phone_number ?? '-');
                $email = $emp->email !== '-' ? e($emp->email) : null;
                if ($email) {
                    return '<div><div class="font-medium text-zinc-800 dark:text-zinc-200">'.$phone.'</div><div class="text-xs text-zinc-400">'.$email.'</div></div>';
                }

                return $phone;
            })
            ->add('status_badge', function (Employee $emp) {
                if ($emp->status === 'active') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Aktif</span>';
                }
                if ($emp->status === 'inactive') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">Nonaktif</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800">'.e(ucfirst($emp->status)).'</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Pegawai', 'employee_display', 'employees.full_name')
                ->searchable()
                ->sortable(),

            Column::make('Unit Kerja', 'unit_name', 'units.name')
                ->searchable()
                ->sortable(),

            Column::make('Jabatan', 'position_title', 'positions.title')
                ->searchable()
                ->sortable(),

            Column::make('Pola Kerja', 'schedule_type_badge', 'employees.schedule_type')
                ->sortable(),

            Column::make('Ikatan Kerja', 'contract_badge'),

            Column::make('Kontak', 'phone_number', 'employees.phone_number')
                ->searchable(),

            Column::make('Status', 'status_badge', 'employees.status')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('employees.full_name')->placeholder('Cari nama...'),
            Filter::inputText('employees.nip')->placeholder('Cari NIP...'),
            Filter::select('employees.schedule_type')
                ->dataSource([
                    ['name' => 'Semua Pola Kerja', 'id' => ''],
                    ['name' => 'Non-Shift (Reguler)', 'id' => 'non_shift'],
                    ['name' => 'Shift (Operasional)', 'id' => 'shift'],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
            Filter::select('employees.status')
                ->dataSource([
                    ['name' => 'Semua Status', 'id' => ''],
                    ['name' => 'Aktif', 'id' => 'active'],
                    ['name' => 'Nonaktif', 'id' => 'inactive'],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(Employee $row): array
    {
        return [
            Button::add('viewProfile')
                ->slot('Lihat Profil')
                ->route('employees.show', ['id' => $row->id])
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer'),
        ];
    }
}
