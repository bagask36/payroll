<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\ScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ScheduleTable extends PowerGridComponent
{
    public string $tableName = 'schedules_table';

    #[Reactive]
    public ?string $weekStartDate = null;

    #[Reactive]
    public string $personTypeFilter = 'all';

    #[Reactive]
    public string $shiftFilter = 'all';

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
        $query = ScheduleAssignment::query()
            ->with(['shift', 'employee.unit', 'doctor'])
            ->orderBy('schedule_date', 'asc');

        if ($this->weekStartDate) {
            $start = Carbon::parse($this->weekStartDate)->startOfDay();
            $end = Carbon::parse($this->weekStartDate)->endOfWeek()->endOfDay();
            $query->whereBetween('schedule_date', [$start, $end]);
        }

        if ($this->personTypeFilter === 'shift_employee') {
            $query->whereHas('employee', fn ($q) => $q->where('schedule_type', 'shift'));
        } elseif ($this->personTypeFilter === 'employee') {
            $query->whereNotNull('employee_id');
        } elseif ($this->personTypeFilter === 'doctor') {
            $query->whereNotNull('doctor_id');
        }

        if ($this->shiftFilter && $this->shiftFilter !== 'all') {
            $query->where('shift_id', $this->shiftFilter);
        }

        return $query;
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('schedule_date_formatted', function (ScheduleAssignment $row) {
                if (! $row->schedule_date) {
                    return '-';
                }
                $dayName = $row->schedule_date->locale('id')->translatedFormat('l');
                $dateFormatted = $row->schedule_date->format('d/m/Y');

                return '
                    <div>
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($dayName).'</div>
                        <div class="text-xs text-zinc-400 font-mono">'.e($dateFormatted).'</div>
                    </div>
                ';
            })
            ->add('personnel', function (ScheduleAssignment $row) {
                if ($row->doctor) {
                    return '
                        <div class="font-semibold text-emerald-700 dark:text-emerald-400">'.e($row->doctor->name).'</div>
                        <div class="text-xs text-zinc-400">'.e($row->doctor->specialization).'</div>
                    ';
                }

                if ($row->employee) {
                    $patternBadge = ($row->employee->schedule_type ?? 'non_shift') === 'shift'
                        ? '<span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300">Shift</span>'
                        : '<span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-950/60 dark:text-teal-300">Non-Shift</span>';

                    return '
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">'.e($row->employee->full_name).'</span>
                            '.$patternBadge.'
                        </div>
                        <div class="text-xs text-zinc-400 font-mono">'.e($row->employee->nip).'</div>
                    ';
                }

                return '<span class="text-zinc-400">-</span>';
            })
            ->add('unit_name', function (ScheduleAssignment $row) {
                if ($row->employee) {
                    return '<span class="text-zinc-600 dark:text-zinc-300 text-xs">'.e($row->employee->unit?->name ?? 'Staf RS').'</span>';
                }
                if ($row->doctor) {
                    return '<span class="text-zinc-600 dark:text-zinc-300 text-xs">Pelayanan Medis</span>';
                }

                return '<span class="text-zinc-400 text-xs">-</span>';
            })
            ->add('shift_details', function (ScheduleAssignment $row) {
                if (! $row->shift) {
                    return '<span class="text-zinc-400">-</span>';
                }
                $start = substr((string) $row->shift->start_time, 0, 5);
                $end = substr((string) $row->shift->end_time, 0, 5);
                $overnight = $row->shift->is_overnight
                    ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800 ml-1">Overnight</span>'
                    : '';

                return '
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($row->shift->name).'</div>
                    <div class="text-xs font-mono text-zinc-500">'.e($start).' - '.e($end).' WIB '.$overnight.'</div>
                ';
            })
            ->add('tolerance', fn (ScheduleAssignment $row) => '<span class="text-xs text-zinc-600 dark:text-zinc-400 font-mono">'.((int) ($row->shift?->late_tolerance_minutes ?? 0)).' Menit</span>')
            ->add('notes', fn (ScheduleAssignment $row) => '<span class="text-xs text-zinc-500 dark:text-zinc-400 max-w-xs truncate block">'.e($row->notes ?: '-').'</span>')
            ->add('status_badge', function (ScheduleAssignment $row) {
                if ($row->status === 'cancelled') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">Dibatalkan</span>';
                }
                if ($row->status === 'completed') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">Selesai</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Terjadwal</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Tanggal Tugas', 'schedule_date_formatted', 'schedule_date')
                ->sortable(),

            Column::make('Personil Medis / Staf', 'personnel'),

            Column::make('Peran / Unit', 'unit_name'),

            Column::make('Shift & Jam Kerja', 'shift_details'),

            Column::make('Toleransi', 'tolerance'),

            Column::make('Catatan', 'notes'),

            Column::make('Status', 'status_badge', 'status')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function actions(ScheduleAssignment $row): array
    {
        $actions = [];

        if ($row->status !== 'cancelled') {
            $actions[] = Button::add('cancel')
                ->slot('Batalkan')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('cancelAssignment', ['assignmentId' => $row->id]);
        }

        return $actions;
    }
}
