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
        if (! $user || (! $user->can('attendance.view_all') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin') && ! $user->hasRole('hr_manager') && ! $user->hasRole('payroll'))) {
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
        $query = Attendance::with(['employee.unit', 'doctor'])
            ->whereDate('date', '>=', $this->startDate)
            ->whereDate('date', '<=', $this->endDate);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->unitFilter) {
            $query->whereHas('employee', function ($q) {
                $q->where('unit_id', $this->unitFilter);
            });
        }

        $records = $query->orderBy('date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-kehadiran-rs-juwita.csv"',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'NIP/Identitas', 'Nama Pegawai/Dokter', 'Unit Kerja', 'Jam Masuk', 'Jam Pulang', 'Status', 'Menit Terlambat', 'Jam Kerja']);

            foreach ($records as $att) {
                $staffName = $att->employee?->full_name ?? ($att->doctor?->name ?? '-');
                $staffNip = $att->employee?->nip ?? ($att->doctor ? 'Dokter' : '-');
                $unitName = $att->employee?->unit?->name ?? ($att->doctor ? 'Pelayanan Medis' : '-');

                fputcsv($file, [
                    $att->date->format('d/m/Y'),
                    $staffNip,
                    $staffName,
                    $unitName,
                    $att->clock_in ? Carbon::parse($att->clock_in)->format('H:i') : '-',
                    $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '-',
                    $att->status,
                    $att->late_duration_minutes ?? 0,
                    $att->work_duration_minutes ? round($att->work_duration_minutes / 60, 1) : 0,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = Attendance::with(['employee.unit', 'doctor'])
            ->whereDate('date', '>=', $this->startDate)
            ->whereDate('date', '<=', $this->endDate);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->unitFilter) {
            $query->whereHas('employee', function ($q) {
                $q->where('unit_id', $this->unitFilter);
            });
        }

        $attendances = $query->orderBy('date', 'desc')->paginate(15);
        $units = Unit::orderBy('name')->get();

        $totalPresent = Attendance::whereDate('date', '>=', $this->startDate)
            ->whereDate('date', '<=', $this->endDate)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $totalLate = Attendance::whereDate('date', '>=', $this->startDate)
            ->whereDate('date', '<=', $this->endDate)
            ->where('status', 'late')
            ->count();

        $totalAbsent = Attendance::whereDate('date', '>=', $this->startDate)
            ->whereDate('date', '<=', $this->endDate)
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
