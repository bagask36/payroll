<?php

namespace App\Livewire\Doctor;

use App\Livewire\Concerns\WithDataTable;
use App\Models\Doctor;
use App\Models\Employee;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule as LivewireRule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Direktori Tenaga Medis & Dokter - RS Permata Bunda')]
class DoctorIndex extends Component
{
    use WithDataTable;

    #[Url(except: 'all')]
    public string $specializationFilter = 'all';

    #[Url(except: 'all')]
    public string $licenseStatusFilter = 'all';

    #[Url(except: 'all')]
    public string $doctorTypeFilter = 'all';

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'specialization' => 'specialization',
            'str_valid_until' => 'str_valid_until',
            'sip_valid_until' => 'sip_valid_until',
            'doctor_type' => fn (Builder $q, string $dir) => $q->orderByRaw("CASE WHEN employee_id IS NOT NULL THEN 1 ELSE 2 END {$dir}"),
        ];
    }

    protected function filterProperties(): array
    {
        return [
            'specializationFilter' => 'all',
            'licenseStatusFilter' => 'all',
            'doctorTypeFilter' => 'all',
        ];
    }

    // Create Doctor Modal State
    public bool $showCreateModal = false;

    public string $newName = '';

    public string $newSpecialization = '';

    public string $newEmail = '';

    public string $newPhone = '';

    public string $newStrNumber = '';

    public string $newStrValidUntil = '';

    public string $newSipNumber = '';

    public string $newSipValidUntil = '';

    public ?int $newEmployeeId = null;

    // Renewal / Edit Modal State
    public bool $showRenewalModal = false;

    public ?int $selectedDoctorId = null;

    public string $doctorName = '';

    public string $doctorEmail = '';

    public string $doctorPhone = '';

    public string $strNumber = '';

    public string $strValidUntil = '';

    public string $sipNumber = '';

    public string $sipValidUntil = '';

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Doctor::class);
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Doctor::class);
        $this->resetValidation();
        $this->reset([
            'newName',
            'newSpecialization',
            'newEmail',
            'newPhone',
            'newStrNumber',
            'newStrValidUntil',
            'newSipNumber',
            'newSipValidUntil',
            'newEmployeeId',
        ]);
        $this->showCreateModal = true;
    }

    public function createDoctor(AuditService $auditService): void
    {
        $this->authorize('create', Doctor::class);

        $this->validate([
            'newName' => ['required', 'string', 'max:150'],
            'newSpecialization' => ['required', 'string', 'max:100'],
            'newEmail' => ['nullable', 'email', 'max:150', 'unique:doctors,email'],
            'newPhone' => ['nullable', 'string', 'max:30'],
            'newStrNumber' => ['required', 'string', 'max:50'],
            'newStrValidUntil' => ['required', 'date'],
            'newSipNumber' => ['required', 'string', 'max:50'],
            'newSipValidUntil' => ['required', 'date'],
            'newEmployeeId' => ['nullable', 'exists:employees,id'],
        ], [
            'newName.required' => 'Nama dokter wajib diisi.',
            'newSpecialization.required' => 'Spesialisasi dokter wajib diisi.',
            'newEmail.email' => 'Format email dokter tidak valid.',
            'newEmail.unique' => 'Email ini sudah terdaftar pada dokter lain.',
            'newStrNumber.required' => 'Nomor STR wajib diisi.',
            'newStrValidUntil.required' => 'Masa berlaku STR wajib diisi.',
            'newSipNumber.required' => 'Nomor SIP wajib diisi.',
            'newSipValidUntil.required' => 'Masa berlaku SIP wajib diisi.',
        ]);

        $doctor = Doctor::create([
            'name' => $this->newName,
            'specialization' => $this->newSpecialization,
            'email' => $this->newEmail ?: null,
            'phone_number' => $this->newPhone ?: null,
            'str_number' => $this->newStrNumber,
            'str_valid_until' => $this->newStrValidUntil,
            'sip_number' => $this->newSipNumber,
            'sip_valid_until' => $this->newSipValidUntil,
            'employee_id' => $this->newEmployeeId,
            'status' => 'active',
        ]);

        $auditService->log(
            action: 'doctor.created',
            auditable: $doctor,
            oldValues: [],
            newValues: [
                'name' => $doctor->name,
                'specialization' => $doctor->specialization,
                'email' => $doctor->email,
                'str_number' => $doctor->str_number,
                'sip_number' => $doctor->sip_number,
                'employee_id' => $doctor->employee_id,
            ]
        );

        $this->showCreateModal = false;
        $this->feedbackMessage = "Data dokter {$doctor->name} berhasil didaftarkan.";
        $this->dispatch('pg:eventRefresh-doctors_table');
    }

    #[On('open-renewal-modal')]
    public function openRenewalModal(int $doctorId): void
    {
        $doctor = Doctor::findOrFail($doctorId);
        $this->authorize('manageCredentials', $doctor);

        $this->selectedDoctorId = $doctor->id;
        $this->doctorName = $doctor->name;
        $this->doctorEmail = (string) ($doctor->email !== '-' ? $doctor->email : '');
        $this->doctorPhone = (string) ($doctor->phone_number ?? '');
        $this->strNumber = $doctor->str_number;
        $this->strValidUntil = $doctor->str_valid_until ? $doctor->str_valid_until->toDateString() : '';
        $this->sipNumber = $doctor->sip_number;
        $this->sipValidUntil = $doctor->sip_valid_until ? $doctor->sip_valid_until->toDateString() : '';
        $this->showRenewalModal = true;
    }

    public function updateCredentials(AuditService $auditService): void
    {
        $doctor = Doctor::findOrFail($this->selectedDoctorId);
        $this->authorize('manageCredentials', $doctor);

        $this->validate([
            'strNumber' => ['required', 'string', 'max:50'],
            'strValidUntil' => ['required', 'date'],
            'sipNumber' => ['required', 'string', 'max:50'],
            'sipValidUntil' => ['required', 'date'],
            'doctorEmail' => ['nullable', 'email', 'max:150', Rule::unique('doctors', 'email')->ignore($doctor->id)],
            'doctorPhone' => ['nullable', 'string', 'max:30'],
        ], [
            'doctorEmail.email' => 'Format alamat email tidak valid.',
            'doctorEmail.unique' => 'Alamat email sudah terdaftar pada dokter lain.',
            'strNumber.required' => 'Nomor STR wajib diisi.',
            'strValidUntil.required' => 'Masa berlaku STR wajib diisi.',
            'sipNumber.required' => 'Nomor SIP wajib diisi.',
            'sipValidUntil.required' => 'Masa berlaku SIP wajib diisi.',
        ]);

        $oldValues = [
            'str_number' => $doctor->str_number,
            'str_valid_until' => $doctor->str_valid_until?->toDateString(),
            'sip_number' => $doctor->sip_number,
            'sip_valid_until' => $doctor->sip_valid_until?->toDateString(),
            'email' => $doctor->email,
            'phone_number' => $doctor->phone_number,
        ];

        $doctor->update([
            'str_number' => $this->strNumber,
            'str_valid_until' => $this->strValidUntil,
            'sip_number' => $this->sipNumber,
            'sip_valid_until' => $this->sipValidUntil,
            'email' => $this->doctorEmail ?: null,
            'phone_number' => $this->doctorPhone ?: null,
        ]);

        $auditService->log(
            action: 'doctor.credentials_updated',
            auditable: $doctor,
            oldValues: $oldValues,
            newValues: [
                'str_number' => $this->strNumber,
                'str_valid_until' => $this->strValidUntil,
                'sip_number' => $this->sipNumber,
                'sip_valid_until' => $this->sipValidUntil,
                'email' => $doctor->email,
                'phone_number' => $doctor->phone_number,
            ]
        );

        $this->showRenewalModal = false;
        $this->feedbackMessage = "Data kredensial dan kontak untuk {$doctor->name} berhasil diperbarui.";
        $this->dispatch('pg:eventRefresh-doctors_table');
    }

    public function render()
    {
        $query = Doctor::with(['employee.unit', 'employee.position']);

        if (! empty($this->search)) {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('specialization', 'like', $term)
                    ->orWhere('str_number', 'like', $term)
                    ->orWhere('sip_number', 'like', $term);
            });
        }

        if ($this->specializationFilter !== 'all') {
            $query->where('specialization', $this->specializationFilter);
        }

        if ($this->doctorTypeFilter === 'organic') {
            $query->whereNotNull('employee_id');
        } elseif ($this->doctorTypeFilter === 'partner') {
            $query->whereNull('employee_id');
        }

        $now = Carbon::today();
        $soonThreshold = Carbon::today()->addDays(60);

        if ($this->licenseStatusFilter === 'expired') {
            $query->where(function ($q) use ($now) {
                $q->whereDate('str_valid_until', '<', $now)
                    ->orWhereDate('sip_valid_until', '<', $now);
            });
        } elseif ($this->licenseStatusFilter === 'expiring_soon') {
            $query->where(function ($q) use ($now, $soonThreshold) {
                $q->whereBetween('str_valid_until', [$now, $soonThreshold])
                    ->orWhereBetween('sip_valid_until', [$now, $soonThreshold]);
            });
        } elseif ($this->licenseStatusFilter === 'valid') {
            $query->whereDate('str_valid_until', '>', $soonThreshold)
                ->whereDate('sip_valid_until', '>', $soonThreshold);
        }

        $this->applySorting($query, 'name', 'asc');

        $doctors = $query->paginate($this->perPageValue());
        $specializations = Doctor::select('specialization')->distinct()->pluck('specialization');

        $stats = [
            'total' => Doctor::count(),
            'organic' => Doctor::whereNotNull('employee_id')->count(),
            'partner' => Doctor::whereNull('employee_id')->count(),
            'attention' => Doctor::where(function ($q) {
                $q->whereDate('str_valid_until', '<', Carbon::today()->addDays(60))
                    ->orWhereDate('sip_valid_until', '<', Carbon::today()->addDays(60));
            })->count(),
        ];

        return view('livewire.doctor.doctor-index', [
            'doctors' => $doctors,
            'specializations' => $specializations,
            'stats' => $stats,
            'organicEmployees' => Employee::active()->orderBy('full_name')->get(['id', 'full_name', 'nip']),
        ]);
    }
}
