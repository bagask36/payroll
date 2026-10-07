<?php

namespace App\Livewire\Attendance;

use App\Contracts\FaceAiService;
use App\Livewire\Concerns\WithDataTable;
use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\FaceBiometricRegistration;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Riwayat Presensi - RS Permata Bunda')]
class AttendanceIndex extends Component
{
    use WithDataTable, WithFileUploads;

    #[Url(except: 'all')]
    public string $statusFilter = 'all';

    #[Url(except: '')]
    public string $startDate = '';

    #[Url(except: '')]
    public string $endDate = '';

    protected function sortableColumns(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'clock_in',
            'clock_out' => 'clock_out',
            'work_duration_minutes' => 'work_duration_minutes',
            'status' => 'status',
        ];
    }

    protected function filterProperties(): array
    {
        return [
            'statusFilter' => 'all',
            'startDate' => Carbon::today()->startOfMonth()->toDateString(),
            'endDate' => Carbon::today()->toDateString(),
        ];
    }

    // Adjustment Request Modal State
    public bool $showAdjustmentModal = false;

    public ?int $selectedAttendanceId = null;

    // Attendance Detail & Evidence Modal State
    public bool $showDetailModal = false;

    public ?Attendance $detailAttendance = null;

    #[Rule(['nullable', 'date_format:H:i'])]
    public string $adjustedClockIn = '';

    #[Rule(['nullable', 'date_format:H:i'])]
    public string $adjustedClockOut = '';

    #[Rule(['required', 'string', 'min:5', 'max:255'])]
    public string $adjustmentReason = '';

    public ?string $feedbackMessage = null;

    // Face Biometric Registration Modal State
    public bool $showFaceRegistrationModal = false;

    public $facePhotoUpload = null;

    public ?string $faceWebcamCapture = null;

    public string $faceRegistrationNotes = '';

    public ?string $faceRegistrationError = null;

    public function openFaceRegistrationModal(): void
    {
        $user = Auth::user();
        if (! $user->employee) {
            $this->feedbackMessage = 'Akun Anda tidak terhubung dengan data pegawai untuk pendaftaran biometrik.';

            return;
        }

        $this->resetFaceRegistrationForm();
        $this->showFaceRegistrationModal = true;
    }

    public function closeFaceRegistrationModal(): void
    {
        $this->resetFaceRegistrationForm();
        $this->showFaceRegistrationModal = false;
    }

    public function resetFaceRegistrationForm(): void
    {
        $this->facePhotoUpload = null;
        $this->faceWebcamCapture = null;
        $this->faceRegistrationNotes = '';
        $this->faceRegistrationError = null;
    }

    public function submitFaceRegistration(FaceAiService $faceAiService, AuditService $auditService): void
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            $this->faceRegistrationError = 'Profil pegawai tidak ditemukan.';

            return;
        }

        $imageBinary = null;

        if ($this->facePhotoUpload) {
            $this->validate([
                'facePhotoUpload' => 'image|max:5120',
            ]);
            $imageBinary = file_get_contents($this->facePhotoUpload->getRealPath());
        } elseif ($this->faceWebcamCapture) {
            $base64 = $this->faceWebcamCapture;
            if (preg_match('/^data:image\/(\w+);base64,/', $base64)) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
            }
            $decoded = base64_decode($base64, true);
            if ($decoded !== false) {
                $imageBinary = $decoded;
            }
        }

        if (empty($imageBinary)) {
            $this->faceRegistrationError = 'Silakan ambil foto wajah dengan kamera atau unggah berkas foto resmi.';

            return;
        }

        // Anti-spoofing liveness verification
        $liveness = $faceAiService->checkLiveness($imageBinary);
        if (! $liveness->isLive) {
            $this->faceRegistrationError = 'Deteksi keaslian wajah gagal (kemungkinan foto kertas / layar): '.$liveness->message;

            return;
        }

        // Store into local private storage
        $filename = 'biometrics/registrations/reg_'.$employee->id.'_'.time().'_'.Str::random(8).'.jpg';
        Storage::disk('local')->put($filename, $imageBinary);

        // Record registration request
        $registration = FaceBiometricRegistration::create([
            'employee_id' => $employee->id,
            'requested_by' => $user->id,
            'photo_path' => $filename,
            'status' => 'pending',
            'notes' => $this->faceRegistrationNotes ?: 'Pengajuan biometrik wajah mandiri presensi',
        ]);

        $auditService->log(
            action: 'employee.face_registration_requested',
            auditable: $registration,
            oldValues: [],
            newValues: [
                'employee_id' => $employee->id,
                'registration_id' => $registration->id,
                'notes' => $registration->notes,
            ]
        );

        $this->closeFaceRegistrationModal();
        $this->feedbackMessage = 'Pengajuan pendaftaran biometrik wajah berhasil dikirimkan. Menunggu persetujuan dari HRD.';
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Attendance::class);
        $this->startDate = Carbon::today()->startOfMonth()->toDateString();
        $this->endDate = Carbon::today()->toDateString();
    }

    #[On('open-adjustment-modal')]
    public function openAdjustmentModal(int $attendanceId): void
    {
        $this->authorize('requestAdjustment', Attendance::class);

        $attendance = Attendance::findOrFail($attendanceId);
        $this->selectedAttendanceId = $attendance->id;
        $this->adjustedClockIn = $attendance->clock_in ? $attendance->clock_in->format('H:i') : '';
        $this->adjustedClockOut = $attendance->clock_out ? $attendance->clock_out->format('H:i') : '';
        $this->adjustmentReason = '';
        $this->showAdjustmentModal = true;
    }

    #[On('open-detail-modal')]
    public function openDetailModal(int $attendanceId): void
    {
        $attendance = Attendance::with(['employee', 'doctor', 'scheduleAssignment.shift', 'events', 'adjustments.requestedBy'])
            ->findOrFail($attendanceId);

        $this->authorize('view', $attendance);

        $this->detailAttendance = $attendance;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailAttendance = null;
    }

    public function submitAdjustment(AuditService $auditService): void
    {
        $this->authorize('requestAdjustment', Attendance::class);
        $this->validate();

        $attendance = Attendance::findOrFail($this->selectedAttendanceId);
        $user = Auth::user();

        $dateString = $attendance->date->toDateString();
        $newClockIn = $this->adjustedClockIn ? Carbon::parse($dateString.' '.$this->adjustedClockIn) : null;
        $newClockOut = $this->adjustedClockOut ? Carbon::parse($dateString.' '.$this->adjustedClockOut) : null;

        $adjustment = AttendanceAdjustment::create([
            'attendance_id' => $attendance->id,
            'original_clock_in' => $attendance->clock_in,
            'adjusted_clock_in' => $newClockIn,
            'original_clock_out' => $attendance->clock_out,
            'adjusted_clock_out' => $newClockOut,
            'reason' => $this->adjustmentReason,
            'status' => 'pending',
            'requested_by' => $user->id,
        ]);

        $auditService->log(
            action: 'attendance.adjustment_requested',
            auditable: $adjustment,
            oldValues: [],
            newValues: [
                'attendance_id' => $attendance->id,
                'reason' => $this->adjustmentReason,
            ]
        );

        $this->showAdjustmentModal = false;
        $this->feedbackMessage = 'Pengajuan koreksi presensi berhasil dikirimkan ke atasan.';
    }

    public function approveAdjustment(int $adjustmentId, AuditService $auditService): void
    {
        $this->authorize('approveAdjustment', Attendance::class);

        $adjustment = AttendanceAdjustment::findOrFail($adjustmentId);
        $user = Auth::user();

        $adjustment->approve($user);

        $auditService->log(
            action: 'attendance.adjustment_approved',
            auditable: $adjustment,
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'approved', 'approved_by' => $user->id],
        );

        $this->feedbackMessage = 'Koreksi presensi berhasil disetujui.';
    }

    public function render()
    {
        $user = Auth::user();
        $query = Attendance::with(['employee', 'doctor', 'scheduleAssignment.shift', 'adjustments']);

        // Search by employee or doctor name/nip
        if (! empty($this->search)) {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('employee', fn ($sq) => $sq->where('full_name', 'like', $term)->orWhere('nip', 'like', $term))
                    ->orWhereHas('doctor', fn ($sq) => $sq->where('name', 'like', $term));
            });
        }

        // RBAC filtering: if cannot view all, filter to own attendance
        if (! $user->hasPermission('attendance.view_all') && ! $user->hasPermission('attendance.view_team')) {
            $employeeId = $user->employee?->id;
            $doctorId = $user->doctor?->id;

            $query->where(function ($q) use ($employeeId, $doctorId) {
                if ($employeeId) {
                    $q->where('employee_id', $employeeId);
                }
                if ($doctorId) {
                    $q->orWhere('doctor_id', $doctorId);
                }
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->startDate) {
            $query->whereDate('date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('date', '<=', $this->endDate);
        }

        $statsQuery = clone $query;
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'present' => (clone $statsQuery)->where('status', 'present')->count(),
            'late' => (clone $statsQuery)->where('status', 'late')->count(),
            'absent' => (clone $statsQuery)->where('status', 'absent')->count(),
        ];

        $this->applySorting($query, 'date', 'desc');

        $attendances = $query->paginate($this->perPageValue());

        // Fetch pending adjustments if user can approve
        $pendingAdjustments = $user->hasPermission('attendance.adjust_approve')
            ? AttendanceAdjustment::with(['attendance.employee', 'attendance.doctor', 'requestedBy'])
                ->where('status', 'pending')
                ->latest()
                ->get()
            : collect();

        $myEmployee = $user->employee;
        $myPendingFaceRegistration = $myEmployee
            ? FaceBiometricRegistration::where('employee_id', $myEmployee->id)->pending()->latest()->first()
            : null;
        $isFaceEnrolled = ! empty($myEmployee?->face_biometric_vector);

        $canManageBiometrics = $user->hasPermission('employee.update') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager');
        $allPendingFaceRegistrations = $canManageBiometrics
            ? FaceBiometricRegistration::with(['employee.unit', 'requestedBy'])->pending()->latest()->get()
            : collect();

        return view('livewire.attendance.attendance-index', [
            'attendances' => $attendances,
            'pendingAdjustments' => $pendingAdjustments,
            'stats' => $stats,
            'myEmployee' => $myEmployee,
            'myPendingFaceRegistration' => $myPendingFaceRegistration,
            'isFaceEnrolled' => $isFaceEnrolled,
            'allPendingFaceRegistrations' => $allPendingFaceRegistrations,
        ]);
    }
}
