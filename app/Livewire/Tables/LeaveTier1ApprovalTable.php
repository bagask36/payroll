<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\LeaveRequest;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class LeaveTier1ApprovalTable extends PowerGridComponent
{
    public string $tableName = 'leave_tier1_approval_table';

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

        if (! $user?->hasPermission('leave.approve_tier1')) {
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
            ->with(['employee.position', 'doctor', 'leaveType'])
            ->where('leave_requests.status', 'pending_tier1')
            ->latest('leave_requests.created_at');

        // Scope to subordinates (plus own requests for the 'Pengajuan Sendiri' action state)
        if ($user->employee && ! $user->isSuperAdmin() && ! $user->hasRole('hr_manager')) {
            $subordinateEmployeeIds = $user->employee->getSubordinates()->pluck('id');
            $allowedEmployeeIds = $subordinateEmployeeIds->push($user->employee->id);

            $query->whereIn('employees.id', $allowedEmployeeIds);
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

                return '<div><div class="font-medium text-zinc-900 dark:text-zinc-100">'.e($name).'</div><div class="text-xs text-zinc-400 font-mono">'.e($sub).'</div></div>';
            })
            ->add('leave_type_badge', fn (LeaveRequest $row) => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">'.e($row->leave_type_name ?? $row->leaveType?->name ?? '-').'</span>')
            ->add('period', fn (LeaveRequest $row) => '<div class="text-xs font-mono text-zinc-700 dark:text-zinc-300">'.e($row->start_date ? $row->start_date->format('d/m/Y') : '-').' - '.e($row->end_date ? $row->end_date->format('d/m/Y') : '-').'</div>')
            ->add('duration', fn (LeaveRequest $row) => '<span class="font-bold text-zinc-900 dark:text-zinc-100">'.e((string) $row->total_days).' Hari</span>')
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

            Column::make('Alasan Cuti', 'reason', 'leave_requests.reason')
                ->searchable(),

            Column::action('Opsi Verifikasi'),
        ];
    }

    public function actions(LeaveRequest $row): array
    {
        $actions = [];
        $user = Auth::user();

        if ($user?->hasPermission('leave.approve_tier1')) {
            if ($row->isApplicant($user) && ! $user->isSuperAdmin()) {
                $actions[] = Button::add('selfRequest')
                    ->slot('Pengajuan Sendiri')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 cursor-not-allowed opacity-80');
            } else {
                $actions[] = Button::add('approveTier1')
                    ->slot('Setujui (T1)')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition-colors cursor-pointer mr-1')
                    ->dispatch('approve-tier1', ['requestId' => $row->id]);

                $actions[] = Button::add('rejectTier1')
                    ->slot('Tolak')
                    ->class('inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shadow-2xs transition-colors cursor-pointer')
                    ->dispatch('open-reject-modal', ['requestId' => $row->id, 'tier' => 1]);
            }
        }

        return $actions;
    }
}
