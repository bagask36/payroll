<?php

namespace App\Livewire\Report;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Laporan Kepegawaian - RS Juwita')]
class EmployeeReportIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all'; // all, active, inactive

    public string $contractTypeFilter = 'all'; // all, permanent, contract, intern

    public ?int $unitFilter = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('employee.view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke laporan kepegawaian ditolak.');
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

    public function updatedContractTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUnitFilter(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $query = Employee::with(['unit', 'position', 'currentEmployment']);

        if ($this->statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('status', '!=', 'active');
        }

        if ($this->contractTypeFilter !== 'all') {
            $query->whereHas('currentEmployment', function ($q) {
                $q->where('contract_type', $this->contractTypeFilter);
            });
        }

        if ($this->unitFilter) {
            $query->where('unit_id', $this->unitFilter);
        }

        $employees = $query->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-kepegawaian-rs-juwita.csv"',
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NIP', 'Nama Lengkap', 'Unit Kerja', 'Jabatan', 'Status Karyawan', 'Tipe Kontrak', 'Tanggal Bergabung']);

            foreach ($employees as $emp) {
                fputcsv($file, [
                    $emp->nip,
                    $emp->name,
                    $emp->unit?->name ?? '-',
                    $emp->position?->title ?? '-',
                    $emp->status,
                    $emp->currentEmployment?->contract_type ?? '-',
                    $emp->currentEmployment?->join_date?->format('d/m/Y') ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = Employee::with(['unit', 'position', 'currentEmployment']);

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'ilike', '%' . $this->search . '%')
                    ->orWhere('nip', 'ilike', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('status', '!=', 'active');
        }

        if ($this->contractTypeFilter !== 'all') {
            $query->whereHas('currentEmployment', function ($q) {
                $q->where('contract_type', $this->contractTypeFilter);
            });
        }

        if ($this->unitFilter) {
            $query->where('unit_id', $this->unitFilter);
        }

        $employees = $query->orderBy('name')->paginate(15);
        $units = Unit::orderBy('name')->get();

        $totalActive = Employee::where('status', 'active')->count();
        $totalPermanent = Employee::whereHas('currentEmployment', fn ($q) => $q->where('contract_type', 'permanent'))->count();
        $totalContract = Employee::whereHas('currentEmployment', fn ($q) => $q->where('contract_type', 'contract'))->count();

        return view('livewire.report.employee-report-index', [
            'employees' => $employees,
            'units' => $units,
            'totalActive' => $totalActive,
            'totalPermanent' => $totalPermanent,
            'totalContract' => $totalContract,
        ]);
    }
}
