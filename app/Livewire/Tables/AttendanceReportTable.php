<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class AttendanceReportTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'attendance_reports_table';

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('laporan-kehadiran-rs-juwita-'.now()->format('Y-m-d'))
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
        return Attendance::query()
            ->leftJoin('employees', 'attendances.employee_id', '=', 'employees.id')
            ->leftJoin('units', 'employees.unit_id', '=', 'units.id')
            ->select([
                'attendances.*',
                'employees.full_name as employee_name',
                'employees.nip as employee_nip',
                'units.name as unit_name',
            ])
            ->latest('attendances.date');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('work_date_display', fn (Attendance $att) => '<span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">'.($att->date ? $att->date->format('d/m/Y') : '-').'</span>')
            ->add('person_display', function (Attendance $att) {
                $name = $att->employee?->full_name ?? $att->employee_name ?? 'Staf';
                $nip = $att->employee?->nip ?? $att->employee_nip ?? '-';

                return '<div><div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($name).'</div><div class="text-xs font-mono text-zinc-400">'.e($nip).'</div></div>';
            })
            ->add('unit_name', fn (Attendance $att) => '<span class="text-zinc-600 dark:text-zinc-400 text-xs">'.e($att->employee?->unit?->name ?? $att->unit_name ?? '-').'</span>')
            ->add('time_display', function (Attendance $att) {
                $in = $att->clock_in ? $att->clock_in->format('H:i') : '--:--';
                $out = $att->clock_out ? $att->clock_out->format('H:i') : '--:--';

                return '<span class="font-mono text-xs font-semibold text-zinc-700 dark:text-zinc-300">'.e($in).' - '.e($out).' WIB</span>';
            })
            ->add('status_badge', function (Attendance $att) {
                if ($att->status === 'present') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Tepat Waktu</span>';
                }
                if ($att->status === 'late') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Terlambat</span>';
                }
                if ($att->status === 'absent') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">Mangkir</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">'.e(ucfirst((string) $att->status)).'</span>';
            })
            ->add('late_display', function (Attendance $att) {
                if (($att->late_duration_minutes ?? 0) > 0) {
                    return '<span class="font-mono text-xs font-semibold text-amber-600 dark:text-amber-400">'.$att->late_duration_minutes.' Menit</span>';
                }

                return '<span class="text-zinc-400 text-xs">0 m</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Tanggal Tugas', 'work_date_display', 'attendances.date')
                ->sortable(),

            Column::make('Pegawai / Staf Medis', 'person_display', 'employees.full_name')
                ->searchable()
                ->sortable(),

            Column::make('Unit Kerja', 'unit_name', 'units.name')
                ->searchable()
                ->sortable(),

            Column::make('Jam Masuk & Pulang', 'time_display'),

            Column::make('Status', 'status_badge', 'attendances.status')
                ->sortable(),

            Column::make('Keterlambatan', 'late_display', 'attendances.late_duration_minutes')
                ->sortable(),
        ];
    }
}
