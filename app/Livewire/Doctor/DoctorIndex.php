<?php

namespace App\Livewire\Doctor;

use App\Models\Doctor;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Direktori Tenaga Medis & Dokter - RS Juwita')]
class DoctorIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $specializationFilter = 'all';

    public string $licenseStatusFilter = 'all';

    public string $doctorTypeFilter = 'all';

    // Renewal Modal State
    public bool $showRenewalModal = false;

    public ?int $selectedDoctorId = null;

    public string $doctorName = '';

    #[Rule(['required', 'string', 'max:50'])]
    public string $strNumber = '';

    #[Rule(['required', 'date'])]
    public string $strValidUntil = '';

    #[Rule(['required', 'string', 'max:50'])]
    public string $sipNumber = '';

    #[Rule(['required', 'date'])]
    public string $sipValidUntil = '';

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Doctor::class);
    }

    public function openRenewalModal(int $doctorId): void
    {
        $doctor = Doctor::findOrFail($doctorId);
        $this->authorize('manageCredentials', $doctor);

        $this->selectedDoctorId = $doctor->id;
        $this->doctorName = $doctor->name;
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
        $this->validate();

        $oldValues = [
            'str_number' => $doctor->str_number,
            'str_valid_until' => $doctor->str_valid_until?->toDateString(),
            'sip_number' => $doctor->sip_number,
            'sip_valid_until' => $doctor->sip_valid_until?->toDateString(),
        ];

        $doctor->update([
            'str_number' => $this->strNumber,
            'str_valid_until' => $this->strValidUntil,
            'sip_number' => $this->sipNumber,
            'sip_valid_until' => $this->sipValidUntil,
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
            ]
        );

        $this->showRenewalModal = false;
        $this->feedbackMessage = "Data kredensial STR dan SIP untuk {$doctor->name} berhasil diperbarui.";
    }

    public function render()
    {
        $query = Doctor::with(['employee.unit', 'employee.position'])->orderBy('name', 'asc');

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

        $doctors = $query->paginate(15);
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
        ]);
    }
}
