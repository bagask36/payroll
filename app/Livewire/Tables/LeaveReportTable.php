<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class LeaveReportTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'leave_reports_table';

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('laporan-cuti-izin-rs-juwita-'.now()->format('Y-m-d'))
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
        return LeaveRequest::query()
            ->leftJoin('employees', 'leave_requests.employee_id', '=', 'employees.id')
            ->leftJoin('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->select([
                'leave_requests.*',
                'employees.full_name as employee_name',
                'employees.nip as employee_nip',
                'leave_types.name as leave_type_name',
            ])
            ->latest('leave_requests.created_at');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('employee_display', function (LeaveRequest $lr) {
                $name = $lr->employee?->full_name ?? $lr->employee_name ?? 'Pegawai';
                $nip = $lr->employee?->nip ?? $lr->employee_nip ?? '-';

                return '<div><div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($name).'</div><div class="text-xs font-mono text-zinc-400">'.e($nip).'</div></div>';
            })
            ->add('leave_type_name', fn (LeaveRequest $lr) => '<span class="font-medium text-zinc-800 dark:text-zinc-200">'.e($lr->leaveType?->name ?? $lr->leave_type_name ?? '-').'</span>')
            ->add('date_range', function (LeaveRequest $lr) {
                $start = $lr->start_date ? $lr->start_date->format('d/m/Y') : '-';
                $end = $lr->end_date ? $lr->end_date->format('d/m/Y') : '-';

                return '<span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">'.e($start).' - '.e($end).'</span>';
            })
            ->add('days_display', fn (LeaveRequest $lr) => '<span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">'.$lr->days_count.' Hari</span>')
            ->add('status_badge', function (LeaveRequest $lr) {
                if ($lr->status === 'approved') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Disetujui</span>';
                }
                if ($lr->status === 'rejected') {
                    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">Ditolak</span>';
                }

                return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Menunggu Review</span>';
            })
            ->add('reason', fn (LeaveRequest $lr) => '<span class="text-zinc-500 text-xs italic">'.e($lr->reason ?? '-').'</span>');
    }

    public function columns(): array
    {
        return [
            Column::make('Pegawai Pemohon', 'employee_display', 'employees.full_name')
                ->searchable()
                ->sortable(),

            Column::make('Jenis Cuti / Izin', 'leave_type_name', 'leave_types.name')
                ->searchable()
                ->sortable(),

            Column::make('Rentang Tanggal', 'date_range'),

            Column::make('Durasi', 'days_display', 'leave_requests.days_count')
                ->sortable(),

            Column::make('Status Permohonan', 'status_badge', 'leave_requests.status')
                ->sortable(),

            Column::make('Alasan', 'reason'),
        ];
    }
}
