<?php

namespace App\Livewire\Report;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Laporan Cuti & Izin - RS Juwita')]
class LeaveReportIndex extends Component
{
    use WithPagination;

    public string $startDate = '';

    public string $endDate = '';

    public string $statusFilter = 'all'; // all, approved, pending, rejected

    public ?int $leaveTypeFilter = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('leave.view_all') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke laporan cuti & izin ditolak.');
        }

        $this->startDate = Carbon::today()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::today()->endOfYear()->format('Y-m-d');
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedLeaveTypeFilter(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $query = LeaveRequest::with(['employee.employment.unit', 'leaveType'])
            ->whereDate('start_date', '>=', $this->startDate)
            ->whereDate('end_date', '<=', $this->endDate);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->leaveTypeFilter) {
            $query->where('leave_type_id', $this->leaveTypeFilter);
        }

        $records = $query->orderBy('start_date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-cuti-izin-rs-juwita.csv"',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No Pengajuan', 'Pegawai', 'Unit Kerja', 'Jenis Cuti', 'Tgl Mulai', 'Tgl Selesai', 'Hari', 'Alasan', 'Status']);

            foreach ($records as $req) {
                fputcsv($file, [
                    $req->request_number ?? '-',
                    $req->employee->name ?? '-',
                    $req->employee->employment?->unit?->name ?? '-',
                    $req->leaveType->name ?? '-',
                    $req->start_date->format('d/m/Y'),
                    $req->end_date->format('d/m/Y'),
                    $req->total_days,
                    $req->reason ?? '-',
                    $req->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = LeaveRequest::with(['employee.employment.unit', 'leaveType'])
            ->whereDate('start_date', '>=', $this->startDate)
            ->whereDate('end_date', '<=', $this->endDate);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->leaveTypeFilter) {
            $query->where('leave_type_id', $this->leaveTypeFilter);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(15);
        $leaveTypes = LeaveType::orderBy('name')->get();

        $totalApproved = LeaveRequest::whereDate('start_date', '>=', $this->startDate)
            ->whereDate('end_date', '<=', $this->endDate)
            ->where('status', 'approved')
            ->count();

        $totalPending = LeaveRequest::whereDate('start_date', '>=', $this->startDate)
            ->whereDate('end_date', '<=', $this->endDate)
            ->whereIn('status', ['submitted', 'pending_approval'])
            ->count();

        $totalDaysTaken = (int) LeaveRequest::whereDate('start_date', '>=', $this->startDate)
            ->whereDate('end_date', '<=', $this->endDate)
            ->where('status', 'approved')
            ->sum('total_days');

        return view('livewire.report.leave-report-index', [
            'leaveRequests' => $leaveRequests,
            'leaveTypes' => $leaveTypes,
            'totalApproved' => $totalApproved,
            'totalPending' => $totalPending,
            'totalDaysTaken' => $totalDaysTaken,
        ]);
    }
}
