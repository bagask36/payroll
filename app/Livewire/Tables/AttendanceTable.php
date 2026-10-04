<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AttendanceTable extends PowerGridComponent
{
    public string $tableName = 'attendances_table';

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
        $user = Auth::user();
        $query = Attendance::query()
            ->with(['employee', 'doctor', 'scheduleAssignment.shift']);

        // RBAC filtering: if cannot view all, filter to own attendance
        if ($user && ! $user->hasPermission('attendance.view_all') && ! $user->hasPermission('attendance.view_team')) {
            $employeeId = $user->employee?->id;
            $doctorId = $user->doctor?->id;

            $query->where(function ($q) use ($employeeId, $doctorId) {
                if ($employeeId) {
                    $q->where('employee_id', $employeeId);
                }
                if ($doctorId) {
                    $q->orWhere('doctor_id', $doctorId);
                }
            });
        }

        return $query;
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('date')
            ->add('date_formatted', fn (Attendance $row) => $row->date ? $row->date->format('d/m/Y') : '-')
            ->add('person_name', function (Attendance $row) {
                $name = $row->employee?->full_name ?? $row->doctor?->name ?? '-';
                $sub = $row->employee?->nip ?? $row->doctor?->specialization ?? '';

                return '
                    <div>
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($name).'</div>
                        <div class="text-xs text-zinc-400">'.e($sub).'</div>
                    </div>
                ';
            })
            ->add('shift_name', fn (Attendance $row) => e($row->scheduleAssignment?->shift?->name ?? 'Reguler'))
            ->add('clock_in_formatted', fn (Attendance $row) => $row->clock_in ? $row->clock_in->format('H:i') : '-')
            ->add('clock_out_formatted', fn (Attendance $row) => $row->clock_out ? $row->clock_out->format('H:i') : '-')
            ->add('duration_formatted', function (Attendance $row) {
                if (! $row->work_duration_minutes) {
                    return '-';
                }
                $hours = intdiv($row->work_duration_minutes, 60);
                $minutes = $row->work_duration_minutes % 60;

                return "{$hours}j {$minutes}m";
            })
            ->add('status_badge', function (Attendance $row) {
                if ($row->status === 'present') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Tepat Waktu</span>';
                }
                if ($row->status === 'late') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Terlambat</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800">Absen</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Tanggal', 'date_formatted', 'date')
                ->sortable(),

            Column::make('Pegawai / Dokter', 'person_name'),

            Column::make('Shift', 'shift_name'),

            Column::make('Masuk', 'clock_in_formatted', 'clock_in')
                ->sortable(),

            Column::make('Pulang', 'clock_out_formatted', 'clock_out')
                ->sortable(),

            Column::make('Durasi', 'duration_formatted', 'work_duration_minutes')
                ->sortable(),

            Column::make('Status', 'status_badge', 'status')
                ->sortable(),

            Column::action('Opsi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status')
                ->dataSource([
                    ['name' => 'Semua Status', 'id' => ''],
                    ['name' => 'Tepat Waktu', 'id' => 'present'],
                    ['name' => 'Terlambat', 'id' => 'late'],
                    ['name' => 'Absen', 'id' => 'absent'],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(Attendance $row): array
    {
        $actions = [];

        $actions[] = Button::add('viewDetail')
            ->slot('Detail')
            ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
            ->dispatch('open-detail-modal', ['attendanceId' => $row->id]);

        if (Auth::user()?->can('attendance.adjust_request')) {
            $actions[] = Button::add('requestAdjustment')
                ->slot('Koreksi')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('open-adjustment-modal', ['attendanceId' => $row->id]);
        }

        return $actions;
    }
}
