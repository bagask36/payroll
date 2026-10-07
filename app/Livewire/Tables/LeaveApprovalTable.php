<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class LeaveApprovalTable extends PowerGridComponent
{
    public string $tableName = 'leave_approval_table';

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

        $canViewApprovals = $user && (
            $user->isSuperAdmin()
            || $user->hasRole('hr_manager')
            || $user->hasPermission('leave.approve_tier1')
            || $user->hasPermission('leave.approve_tier2')
            || ($user->employee && $user->employee->getSubordinates()->isNotEmpty())
        );

        if (! $canViewApprovals) {
            return LeaveRequest::query()->whereRaw('1 = 0');
        }

        $query = LeaveRequest::query()
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
            ->with(['employee.position.unit', 'doctor', 'leaveType', 'approvals.approver'])
            ->latest('leave_requests.created_at');

        // Non-admin / non-HR approvers only see their subordinates, own requests, or requests they previously approved
        if ($user->employee && ! $user->isSuperAdmin() && ! $user->hasRole('hr_manager')) {
            $subordinateEmployeeIds = $user->employee->getSubordinates()->pluck('id');
            $previouslyApprovedRequestIds = LeaveApproval::where('approver_id', $user->id)->pluck('leave_request_id');

            $allowedEmployeeIds = $subordinateEmployeeIds->push($user->employee->id);

            $query->where(function (Builder $subQ) use ($allowedEmployeeIds, $previouslyApprovedRequestIds) {
                $subQ->whereIn('employees.id', $allowedEmployeeIds)
                    ->orWhereIn('leave_requests.id', $previouslyApprovedRequestIds);
            });
        }

        return $query;
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('applicant_display', function (LeaveRequest $row) {
                $name = $row->employee?->full_name ?? $row->doctor?->name ?? 'Staf';
                $sub = $row->employee?->nip ?? $row->doctor?->specialization ?? '';
                $unit = $row->employee?->position?->unit?->name ?? $row->employee?->position?->title ?? '';

                $unitHtml = $unit !== '' ? '<div class="text-[11px] text-zinc-500 truncate max-w-xs">'.e($unit).'</div>' : '';

                return '<div>
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($name).'</div>
                    <div class="text-xs text-zinc-400 font-mono">'.e($sub).'</div>
                    '.$unitHtml.'
                </div>';
            })
            ->add('leave_type_badge', fn (LeaveRequest $row) => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">'.e($row->leave_type_name ?? $row->leaveType?->name ?? '-').'</span>')
            ->add('period', fn (LeaveRequest $row) => '<div class="text-xs font-mono text-zinc-700 dark:text-zinc-300">'.e($row->start_date ? $row->start_date->format('d/m/Y') : '-').' s/d '.e($row->end_date ? $row->end_date->format('d/m/Y') : '-').'</div>')
            ->add('duration', fn (LeaveRequest $row) => '<span class="font-bold text-zinc-900 dark:text-zinc-100">'.e((string) $row->total_days).' Hari</span>')
            ->add('stage_progress', function (LeaveRequest $row) {
                $chain = $row->getApprovalChain();
                $totalStages = count($chain);
                $approvedCount = $row->approvals->where('status', 'approved')->count();

                if ($row->status === 'approved') {
                    return '<span class="inline-flex items-center text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <svg class="size-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Selesai ('.$totalStages.'/'.$totalStages.')
                    </span>';
                }

                if ($row->status === 'rejected') {
                    return '<span class="text-xs font-medium text-rose-600 dark:text-rose-400">Ditolak</span>';
                }

                if ($row->status === 'cancelled') {
                    return '<span class="text-xs font-medium text-zinc-400">Dibatalkan</span>';
                }

                $currentStage = $row->getCurrentPendingStage();
                $currentTitle = $currentStage['title'] ?? 'Tahap Aktif';

                return '<div class="text-xs">
                    <span class="font-medium text-amber-700 dark:text-amber-300">Tahap '.($approvedCount + 1).' dari '.$totalStages.'</span>
                    <span class="block text-[11px] text-zinc-500 truncate max-w-xs">'.e($currentTitle).'</span>
                </div>';
            })
            ->add('status_badge', function (LeaveRequest $row) {
                return match ($row->status) {
                    'approved' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Disetujui</span>',
                    'rejected' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">Ditolak</span>',
                    'cancelled' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-600 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700">Dibatalkan</span>',
                    default => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">Menunggu Persetujuan</span>',
                };
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

            Column::make('Status', 'status_badge', 'leave_requests.status')
                ->sortable(),

            Column::make('Tahapan Hierarkis', 'stage_progress'),

            Column::make('Alasan', 'reason', 'leave_requests.reason')
                ->searchable(),

            Column::action('Opsi Aksi'),
        ];
    }

    public function actions(LeaveRequest $row): array
    {
        $actions = [];
        $user = Auth::user();

        // 1. Detail button is always visible
        $actions[] = Button::add('detail')
            ->slot('Detail')
            ->class('inline-flex items-center px-2 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer mr-1.5')
            ->dispatch('open-detail-modal', ['requestId' => $row->id]);

        // 2. Action based on status
        if ($row->status === 'approved') {
            $actions[] = Button::add('statusApproved')
                ->slot('✓ Disetujui')
                ->class('inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 cursor-default opacity-90');
        } elseif ($row->status === 'rejected') {
            $actions[] = Button::add('statusRejected')
                ->slot('Ditolak')
                ->class('inline-flex items-center px-2 py-1 text-xs font-medium rounded-lg bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800 cursor-default opacity-80');
        } elseif ($row->status === 'cancelled') {
            $actions[] = Button::add('statusCancelled')
                ->slot('Dibatalkan')
                ->class('inline-flex items-center px-2 py-1 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 cursor-default opacity-80');
        } else {
            // Pending states
            if ($row->isApplicant($user) && ! $user->isSuperAdmin()) {
                $actions[] = Button::add('selfRequest')
                    ->slot('Pengajuan Sendiri')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 cursor-not-allowed opacity-80');
            } elseif ($row->canUserApprove($user)) {
                $currentStage = $row->getCurrentPendingStage();
                $tier = $currentStage['tier'] ?? 1;

                $actions[] = Button::add('approveHierarchical')
                    ->slot('Setujui')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition-colors cursor-pointer mr-1')
                    ->dispatch('approve-hierarchical', ['requestId' => $row->id]);

                $actions[] = Button::add('rejectHierarchical')
                    ->slot('Tolak')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shadow-2xs transition-colors cursor-pointer')
                    ->dispatch('open-reject-modal', ['requestId' => $row->id, 'tier' => $tier]);
            } else {
                $actions[] = Button::add('waitingOther')
                    ->slot('Menunggu Tahap Lain')
                    ->class('inline-flex items-center px-2 py-1 text-xs font-medium rounded-lg bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800 cursor-default opacity-80');
            }
        }

        return $actions;
    }
}
