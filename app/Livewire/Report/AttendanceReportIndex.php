<?php

namespace App\Livewire\Report;

use App\Models\Attendance;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Laporan Kehadiran - RS Juwita')]
class AttendanceReportIndex extends Component
{
    use WithPagination;

    public string $startDate = '';

    public string $endDate = '';

    public string $statusFilter = 'all'; // all, present, late, absent

    public ?int $unitFilter = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('attendance.view_all') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke laporan kehadiran ditolak.');
        }

        $this->startDate = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
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

    public function updatedUnitFilter(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $query = Attendance::with(['employee.employment.unit'])
            ->whereDate('work_date', '>=', $this->startDate)
            ->whereDate('work_date', '<=', $this->endDate);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->unitFilter) {
            $query->whereHas('employee.employment', function ($q) {
                $q->where('unit_id', $this->unitFilter);
            });
        }

        $records = $query->orderBy('work_date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-kehadiran-rs-juwita.csv"',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'NIP', 'Nama Pegawai', 'Unit Kerja', 'Jam Masuk', 'Jam Pulang', 'Status', 'Menit Terlambat', 'Jam Kerja']);

            foreach ($records as $att) {
                fputcsv($file, [
                    $att->work_date->format('d/m/Y'),
                    $att->employee->nip ?? '-',
                    $att->employee->name ?? '-',
                    $att->employee->employment?->unit?->name ?? '-',
                    $att->clock_in_time ? Carbon::parse($att->clock_in_time)->format('H:i') : '-',
                    $att->clock_out_time ? Carbon::parse($att->clock_out_time)->format('H:i') : '-',
                    $att->status,
                    $att->late_minutes ?? 0,
                    $att->work_hours ?? 0,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = Attendance::with(['employee.employment.unit'])
            ->whereDate('work_date', '>=', $this->startDate)
            ->whereDate('work_date', '<=', $this->endDate);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->unitFilter) {
            $query->whereHas('employee.employment', function ($q) {
                $q->where('unit_id', $this->unitFilter);
            });
        }

        $attendances = $query->orderBy('work_date', 'desc')->paginate(15);
        $units = Unit::orderBy('name')->get();

        $totalPresent = Attendance::whereDate('work_date', '>=', $this->startDate)
            ->whereDate('work_date', '<=', $this->endDate)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $totalLate = Attendance::whereDate('work_date', '>=', $this->startDate)
            ->whereDate('work_date', '<=', $this->endDate)
            ->where('status', 'late')
            ->count();

        $totalAbsent = Attendance::whereDate('work_date', '>=', $this->startDate)
            ->whereDate('work_date', '<=', $this->endDate)
            ->where('status', 'absent')
            ->count();

        return view('livewire.report.attendance-report-index', [
            'attendances' => $attendances,
            'units' => $units,
            'totalPresent' => $totalPresent,
            'totalLate' => $totalLate,
            'totalAbsent' => $totalAbsent,
        ]);
    }
}
