<?php

namespace App\Livewire\Report;

use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Laporan Tenaga Medis & Dokter - RS Juwita')]
class DoctorReportIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all'; // all, active, inactive

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('doctor.view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin') && ! $user->hasRole('hr_manager'))) {
            abort(403, 'Akses ke laporan dokter ditolak.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $query = Doctor::with(['employee']);

        if ($this->statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('status', '!=', 'active');
        }

        $doctors = $query->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-dokter-rs-juwita.csv"',
        ];

        $callback = function () use ($doctors) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nama Dokter', 'Spesialisasi', 'No STR', 'Masa Berlaku STR', 'No SIP', 'Masa Berlaku SIP', 'Status']);

            foreach ($doctors as $doc) {
                fputcsv($file, [
                    $doc->name,
                    $doc->specialization ?? 'Dokter Umum',
                    $doc->str_number ?? '-',
                    $doc->str_valid_until ? $doc->str_valid_until->format('d/m/Y') : '-',
                    $doc->sip_number ?? '-',
                    $doc->sip_valid_until ? $doc->sip_valid_until->format('d/m/Y') : '-',
                    $doc->status === 'active' ? 'Aktif' : 'Nonaktif',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = Doctor::with(['employee']);

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'ilike', '%' . $this->search . '%')
                    ->orWhere('specialization', 'ilike', '%' . $this->search . '%')
                    ->orWhere('str_number', 'ilike', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('status', '!=', 'active');
        }

        $doctors = $query->orderBy('name')->paginate(15);

        $totalActive = Doctor::where('status', 'active')->count();
        $totalSpecialists = Doctor::whereNotNull('specialization')->where('specialization', '!=', 'Umum')->where('specialization', '!=', 'Dokter Umum')->count();
        $totalGeneral = Doctor::count() - $totalSpecialists;

        return view('livewire.report.doctor-report-index', [
            'doctors' => $doctors,
            'totalActive' => $totalActive,
            'totalSpecialists' => $totalSpecialists,
            'totalGeneral' => $totalGeneral,
        ]);
    }
}
