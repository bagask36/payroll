<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class LeaveRequestTable extends PowerGridComponent
{
    public string $tableName = 'leave_requests_table';

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
        $employeeId = $user?->employee?->id;
        $doctorId = $user?->doctor?->id;

        return LeaveRequest::query()
            ->leftJoin('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->select([
                'leave_requests.*',
                'leave_types.name as leave_type_name',
            ])
            ->with(['leaveType', 'approvals.approver'])
            ->where(function ($q) use ($employeeId, $doctorId, $user) {
                if ($employeeId) {
                    $q->where('leave_requests.employee_id', $employeeId);
                } elseif ($doctorId) {
                    $q->where('leave_requests.doctor_id', $doctorId);
                } else {
                    $q->where('leave_requests.created_by', $user?->id);
                }
            });
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('created_at_formatted', fn (LeaveRequest $row) => '<div class="text-xs font-mono text-zinc-500">'.e($row->created_at->format('d/m/Y H:i')).'</div>')
            ->add('leave_type_name', fn (LeaveRequest $row) => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">'.e($row->leave_type_name ?? $row->leaveType?->name ?? '-').'</span>')
            ->add('period', fn (LeaveRequest $row) => '<div class="text-xs font-mono text-zinc-700 dark:text-zinc-300">'.e($row->start_date->format('d/m/Y')).' - '.e($row->end_date->format('d/m/Y')).'</div>')
            ->add('duration', fn (LeaveRequest $row) => '<span class="font-semibold text-zinc-900 dark:text-zinc-100">'.e((string) $row->total_days).' Hari</span>')
            ->add('reason', fn (LeaveRequest $row) => '<div class="text-xs text-zinc-600 dark:text-zinc-400 max-w-xs truncate" title="'.e($row->reason).'">'.e($row->reason).'</div>')
            ->add('status_badge', function (LeaveRequest $row) {
                return match ($row->status) {
                    'approved' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Disetujui</span>',
                    'rejected' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">Ditolak</span>',
                    'cancelled' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-600 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700">Dibatalkan</span>',
                    'pending_tier2' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">Menunggu SDM (T2)</span>',
                    default => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Menunggu Supervisor (T1)</span>',
                };
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Tanggal Pengajuan', 'created_at_formatted', 'leave_requests.created_at')
                ->sortable(),

            Column::make('Jenis Cuti', 'leave_type_name', 'leave_types.name')
                ->searchable(),

            Column::make('Periode Cuti', 'period', 'leave_requests.start_date')
                ->sortable(),

            Column::make('Durasi', 'duration', 'leave_requests.total_days')
                ->sortable(),

            Column::make('Alasan', 'reason', 'leave_requests.reason')
                ->searchable(),

            Column::make('Status', 'status_badge', 'leave_requests.status')
                ->sortable(),

            Column::action('Aksi'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('leave_requests.status')
                ->dataSource([
                    ['name' => 'Semua Status', 'id' => ''],
                    ['name' => 'Menunggu Supervisor (T1)', 'id' => 'pending_tier1'],
                    ['name' => 'Menunggu SDM (T2)', 'id' => 'pending_tier2'],
                    ['name' => 'Disetujui', 'id' => 'approved'],
                    ['name' => 'Ditolak', 'id' => 'rejected'],
                    ['name' => 'Dibatalkan', 'id' => 'cancelled'],
                ])
                ->optionValue('id')
                ->optionLabel('name'),
        ];
    }

    public function actions(LeaveRequest $row): array
    {
        $actions = [];
        $user = Auth::user();

        $actions[] = Button::add('viewDetail')
            ->slot('Detail')
            ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 shadow-2xs transition-colors cursor-pointer mr-1.5')
            ->dispatch('open-detail-modal', ['requestId' => $row->id]);

        if ($row->status === 'pending_tier1' && ($row->isApplicant($user) || $user?->can('cancel', $row))) {
            $actions[] = Button::add('cancelRequest')
                ->slot('Batalkan')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shadow-2xs transition-colors cursor-pointer')
                ->dispatch('cancel-leave-request', ['requestId' => $row->id]);
        }

        return $actions;
    }
}
