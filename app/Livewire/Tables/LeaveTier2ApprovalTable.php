<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class LeaveTier2ApprovalTable extends PowerGridComponent
{
    public string $tableName = 'leave_tier2_approval_table';

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

        if (! $user?->hasPermission('leave.approve_tier2')) {
            return LeaveRequest::query()->whereRaw('1 = 0');
        }

        return LeaveRequest::query()
            ->leftJoin('employees', 'leave_requests.employee_id', '=', 'employees.id')
            ->leftJoin('doctors', 'leave_requests.doctor_id', '=', 'doctors.id')
            ->leftJoin('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->select([
                'leave_requests.*',
                'employees.full_name as employee_name',
                'employees.nip as employee_nip',
                'doctors.name as doctor_name',
                'doctors.specialization as doctor_spec',
                'leave_types.name as leave_type_name',
            ])
            ->with(['employee', 'doctor', 'leaveType', 'approvals.approver'])
            ->latest('leave_requests.created_at');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('applicant_display', function (LeaveRequest $row) {
                $name = $row->employee?->full_name ?? $row->doctor?->name ?? 'Staf';
                $sub = $row->employee?->nip ?? $row->doctor?->specialization ?? '';

                return '<div><div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($name).'</div><div class="text-xs text-zinc-400 font-mono">'.e($sub).'</div></div>';
            })
            ->add('leave_type_badge', fn (LeaveRequest $row) => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">'.e($row->leave_type_name ?? $row->leaveType?->name ?? '-').'</span>')
            ->add('period', fn (LeaveRequest $row) => '<div class="text-xs font-mono text-zinc-700 dark:text-zinc-300">'.e($row->start_date ? $row->start_date->format('d/m/Y') : '-').' - '.e($row->end_date ? $row->end_date->format('d/m/Y') : '-').'</div>')
            ->add('duration', fn (LeaveRequest $row) => '<span class="font-bold text-zinc-900 dark:text-zinc-100">'.e((string) $row->total_days).' Hari</span>')
            ->add('tier1_verification', function (LeaveRequest $row) {
                $t1 = $row->approvals->firstWhere('tier', 1);
                if ($t1) {
                    $approver = e($t1->approver?->name ?? 'Supervisor');
                    $time = $t1->action_at ? e($t1->action_at->format('d/m/Y H:i')) : '-';

                    return '<span class="text-emerald-600 font-medium text-xs">✓ Disetujui: '.$approver.'</span><span class="text-zinc-400 block text-xs font-mono">('.$time.')</span>';
                }

                return '<span class="text-zinc-400 text-xs">-</span>';
            })
            ->add('reason', fn (LeaveRequest $row) => '<div class="text-xs text-zinc-600 dark:text-zinc-400 max-w-xs truncate" title="'.e($row->reason ?? '').'">'.e($row->reason ?? '-').'</div>');
    }

    public function columns(): array
    {
        return [
            Column::make('Pemohon', 'applicant_display', 'employees.full_name')
                ->searchable()
                ->sortable(),

            Column::make('Jenis Cuti', 'leave_type_badge', 'leave_types.name')
                ->searchable()
                ->sortable(),

            Column::make('Periode Cuti', 'period', 'leave_requests.start_date')
                ->sortable(),

            Column::make('Durasi', 'duration', 'leave_requests.total_days')
                ->sortable(),

            Column::make('Verifikasi Supervisor', 'tier1_verification'),

            Column::make('Alasan Cuti', 'reason', 'leave_requests.reason')
                ->searchable(),

            Column::action('Opsi Final'),
        ];
    }

    public function actions(LeaveRequest $row): array
    {
        $actions = [];
        $user = Auth::user();

        if ($row->status === 'approved') {
            $actions[] = Button::add('statusApproved')
                ->slot('✓ Disetujui')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 cursor-default opacity-90');

            return $actions;
        }

        if ($row->status === 'rejected') {
            $actions[] = Button::add('statusRejected')
                ->slot('Ditolak')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800 cursor-default opacity-80');

            return $actions;
        }

        if ($user?->hasPermission('leave.approve_tier2')) {
            if ($row->isApplicant($user) && ! $user->isSuperAdmin()) {
                $actions[] = Button::add('selfRequest')
                    ->slot('Pengajuan Sendiri')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 cursor-not-allowed opacity-80');
            } else {
                $actions[] = Button::add('approveTier2')
                    ->slot('Setujui Final (T2)')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-purple-600 hover:bg-purple-700 text-white shadow-2xs transition-colors cursor-pointer mr-1')
                    ->dispatch('approve-tier2', ['requestId' => $row->id]);

                $actions[] = Button::add('rejectTier2')
                    ->slot('Tolak')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shadow-2xs transition-colors cursor-pointer')
                    ->dispatch('open-reject-modal', ['requestId' => $row->id, 'tier' => 2]);
            }
        }

        return $actions;
    }
}
