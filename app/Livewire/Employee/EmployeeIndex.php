<?php

namespace App\Livewire\Employee;

use App\Livewire\Concerns\WithDataTable;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\Position;
use App\Models\Unit;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Direktori Pegawai - RS Juwita')]
class EmployeeIndex extends Component
{
    use WithDataTable;

    #[Url(except: 'all')]
    public string $unitFilter = 'all';

    #[Url(except: 'active')]
    public string $statusFilter = 'active';

    #[Url(except: 'all')]
    public string $contractFilter = 'all';

    // Create Employee Modal State
    public bool $showCreateModal = false;

    #[Rule(['required', 'string', 'max:20', 'unique:employees,nip'])]
    public string $nip = '';

    #[Rule(['required', 'string', 'max:150'])]
    public string $name = '';

    #[Rule(['required', 'string', 'max:25'])]
    public string $phone = '';

    #[Rule(['required', 'in:male,female'])]
    public string $gender = 'male';

    #[Rule(['required', 'date'])]
    public string $birthDate = '';

    #[Rule(['required', 'string', 'max:50'])]
    public string $birthPlace = 'Jakarta';

    #[Rule(['required', 'string', 'size:16'])]
    public string $ktpNumber = '';

    #[Rule(['required', 'string', 'max:255'])]
    public string $address = 'Jl. Teratai No. 12, Jakarta';

    #[Rule(['required', 'exists:units,id'])]
    public ?int $unitId = null;

    #[Rule(['required', 'exists:positions,id'])]
    public ?int $positionId = null;

    #[Rule(['required', 'in:permanent,contract,probation'])]
    public string $contractType = 'permanent';

    #[Rule(['required', 'date'])]
    public string $joinDate = '';

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Employee::class);
        $this->joinDate = Carbon::today()->toDateString();
        $this->birthDate = Carbon::today()->subYears(25)->toDateString();
        $this->birthPlace = 'Jakarta';
        $this->address = 'Jl. Teratai No. 12, Jakarta';
    }

    protected function sortableColumns(): array
    {
        return [
            'full_name' => 'full_name',
            'nip' => 'nip',
            'status' => 'status',
            'unit' => fn (Builder $q, string $dir) => $q->orderBy(
                Unit::select('name')->whereColumn('units.id', 'employees.unit_id'),
                $dir
            ),
            'position' => fn (Builder $q, string $dir) => $q->orderBy(
                Position::select('title')->whereColumn('positions.id', 'employees.position_id'),
                $dir
            ),
        ];
    }

    protected function filterProperties(): array
    {
        return [
            'unitFilter' => 'all',
            'statusFilter' => 'active',
            'contractFilter' => 'all',
        ];
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Employee::class);
        $this->resetValidation();
        $this->nip = 'PEG-'.Carbon::now()->format('Ym').str_pad((string) rand(1, 999), 3, '0', STR_PAD_LEFT);
        $this->showCreateModal = true;
    }

    public function createEmployee(AuditService $auditService): void
    {
        $this->authorize('create', Employee::class);
        $this->validate();

        $employee = Employee::create([
            'nip' => $this->nip,
            'full_name' => $this->name,
            'phone_number' => $this->phone,
            'gender' => $this->gender,
            'birth_place' => $this->birthPlace,
            'birth_date' => $this->birthDate,
            'ktp_number' => $this->ktpNumber,
            'residential_address' => $this->address,
            'unit_id' => $this->unitId,
            'position_id' => $this->positionId,
            'status' => 'active',
        ]);

        EmployeeEmployment::create([
            'employee_id' => $employee->id,
            'contract_type' => $this->contractType,
            'contract_number' => 'KTR/RSJ/'.Carbon::now()->format('Ym').'/'.str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT),
            'start_date' => $this->joinDate,
            'is_active' => true,
        ]);

        $auditService->log(
            action: 'employee.created',
            auditable: $employee,
            oldValues: [],
            newValues: [
                'nip' => $this->nip,
                'name' => $this->name,
                'unit_id' => $this->unitId,
                'position_id' => $this->positionId,
            ]
        );

        $this->showCreateModal = false;
        $this->feedbackMessage = "Pegawai {$this->name} (NIP: {$this->nip}) berhasil didaftarkan.";
        $this->reset(['name', 'phone', 'ktpNumber']);
    }

    public function render()
    {
        $query = Employee::with(['unit', 'position', 'currentEmployment']);

        if (! empty($this->search)) {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', $term)
                    ->orWhere('nip', 'like', $term);
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', strtolower($this->statusFilter));
        }

        if ($this->unitFilter !== 'all') {
            $query->where('unit_id', $this->unitFilter);
        }

        if ($this->contractFilter !== 'all') {
            $query->whereHas('currentEmployment', function ($q) {
                $q->where('contract_type', $this->contractFilter);
            });
        }

        $this->applySorting($query, 'full_name', 'asc');

        $employees = $query->paginate($this->perPageValue());
        $units = Unit::orderBy('name')->get();
        $positions = Position::orderBy('title')->get();

        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('status', 'active')->count(),
            'permanent' => Employee::whereHas('currentEmployment', fn ($q) => $q->where('contract_type', 'permanent'))->count(),
            'contract' => Employee::whereHas('currentEmployment', fn ($q) => $q->where('contract_type', 'contract'))->count(),
        ];

        return view('livewire.employee.employee-index', [
            'employees' => $employees,
            'units' => $units,
            'positions' => $positions,
            'stats' => $stats,
        ]);
    }
}
