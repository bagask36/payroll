<?php

namespace App\Livewire\Doctor;

use App\Models\Doctor;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Document\DocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Profil Tenaga Medis & Dokter - RS Permata Bunda')]
class DoctorShow extends Component
{
    use WithFileUploads;

    public Doctor $doctor;

    public string $activeTab = 'bio';

    // --- BIODATA MODAL STATE ---
    public bool $showBioModal = false;

    public string $bioName = '';

    public string $bioSpecialization = '';

    public string $bioEmail = '';

    public string $bioPhone = '';

    public string $bioStatus = 'active';

    public ?string $bioFeedback = null;

    public ?string $bioError = null;

    // --- CREDENTIAL MODAL STATE ---
    public bool $showCredentialModal = false;

    public string $strNumber = '';

    public string $strValidUntil = '';

    public string $sipNumber = '';

    public string $sipValidUntil = '';

    public ?string $credFeedback = null;

    public ?string $credError = null;

    // --- LINK EMPLOYEE MODAL STATE ---
    public bool $showLinkModal = false;

    public ?int $selectedEmployeeId = null;

    public ?string $linkFeedback = null;

    public ?string $linkError = null;

    // --- DOCUMENT UPLOAD MODAL STATE ---
    public bool $showDocModal = false;

    public string $docUploadType = 'str';

    public $docUploadedFile = null;

    public ?string $docFeedback = null;

    public ?string $docError = null;

    // --- PORTAL USER ACCOUNT STATE ---
    public bool $showAccountModal = false;

    public string $accountEmail = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public ?string $generatedPasswordNotice = null;

    public bool $userIsActive = true;

    public ?string $accountFeedback = null;

    public ?string $accountError = null;

    public function mount(int $id): void
    {
        $this->doctor = Doctor::with([
            'user.roles',
            'employee.unit',
            'employee.position',
            'employee.currentEmployment',
            'scheduleAssignments' => fn ($q) => $q->with(['shift', 'unit'])->orderBy('schedule_date', 'desc')->limit(20),
            'attendances' => fn ($q) => $q->with(['scheduleAssignment.shift'])->orderBy('date', 'desc')->limit(20),
            'leaveBalances.leaveType',
            'leaveRequests' => fn ($q) => $q->with(['leaveType'])->orderBy('start_date', 'desc')->limit(20),
            'documents.uploadedBy',
        ])->findOrFail($id);

        $this->authorize('view', $this->doctor);

        if (request()->has('tab') && in_array(request()->query('tab'), ['bio', 'schedules', 'attendance', 'leave', 'documents', 'account'])) {
            $this->activeTab = (string) request()->query('tab');
        }

        $this->syncFormData();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetMessages();
    }

    public function resetMessages(): void
    {
        $this->bioFeedback = null;
        $this->bioError = null;
        $this->credFeedback = null;
        $this->credError = null;
        $this->linkFeedback = null;
        $this->linkError = null;
        $this->docFeedback = null;
        $this->docError = null;
        $this->accountFeedback = null;
        $this->accountError = null;
        $this->generatedPasswordNotice = null;
    }

    private function syncFormData(): void
    {
        $this->bioName = $this->doctor->name;
        $this->bioSpecialization = $this->doctor->specialization;
        $this->bioEmail = (string) ($this->doctor->email !== '-' ? $this->doctor->email : '');
        $this->bioPhone = (string) ($this->doctor->phone_number ?? '');
        $this->bioStatus = $this->doctor->status;

        $this->strNumber = (string) ($this->doctor->str_number ?? '');
        $this->strValidUntil = $this->doctor->str_valid_until ? $this->doctor->str_valid_until->toDateString() : '';
        $this->sipNumber = (string) ($this->doctor->sip_number ?? '');
        $this->sipValidUntil = $this->doctor->sip_valid_until ? $this->doctor->sip_valid_until->toDateString() : '';

        $this->selectedEmployeeId = $this->doctor->employee_id;

        if ($this->doctor->user) {
            $this->accountEmail = $this->doctor->user->email;
            $this->userIsActive = (bool) $this->doctor->user->is_active;
        } else {
            $this->accountEmail = $this->bioEmail;
            $this->userIsActive = true;
        }
    }

    // ==========================================
    // BIODATA & CONTACT
    // ==========================================

    public function openBioModal(): void
    {
        $this->authorize('update', $this->doctor);
        $this->resetMessages();
        $this->resetValidation();
        $this->syncFormData();
        $this->showBioModal = true;
    }

    public function saveBio(AuditService $auditService): void
    {
        $this->authorize('update', $this->doctor);
        $this->resetMessages();

        $this->validate([
            'bioName' => ['required', 'string', 'max:150'],
            'bioSpecialization' => ['required', 'string', 'max:100'],
            'bioEmail' => ['nullable', 'email', 'max:150', Rule::unique('doctors', 'email')->ignore($this->doctor->id)],
            'bioPhone' => ['nullable', 'string', 'max:30'],
            'bioStatus' => ['required', 'in:active,inactive'],
        ], [
            'bioName.required' => 'Nama dokter wajib diisi.',
            'bioSpecialization.required' => 'Spesialisasi wajib diisi.',
            'bioEmail.email' => 'Format email tidak valid.',
            'bioEmail.unique' => 'Email ini sudah digunakan oleh dokter lain.',
        ]);

        $oldValues = [
            'name' => $this->doctor->name,
            'specialization' => $this->doctor->specialization,
            'email' => $this->doctor->email,
            'phone_number' => $this->doctor->phone_number,
            'status' => $this->doctor->status,
        ];

        $this->doctor->update([
            'name' => $this->bioName,
            'specialization' => $this->bioSpecialization,
            'email' => $this->bioEmail ?: null,
            'phone_number' => $this->bioPhone ?: null,
            'status' => $this->bioStatus,
        ]);

        $auditService->log(
            action: 'doctor.updated',
            auditable: $this->doctor,
            oldValues: $oldValues,
            newValues: [
                'name' => $this->doctor->name,
                'specialization' => $this->doctor->specialization,
                'email' => $this->doctor->email,
                'phone_number' => $this->doctor->phone_number,
                'status' => $this->doctor->status,
            ]
        );

        $this->showBioModal = false;
        $this->bioFeedback = 'Informasi profil dokter berhasil diperbarui.';
    }

    // ==========================================
    // STR & SIP CREDENTIALS
    // ==========================================

    public function openCredentialModal(): void
    {
        $this->authorize('manageCredentials', $this->doctor);
        $this->resetMessages();
        $this->resetValidation();
        $this->syncFormData();
        $this->showCredentialModal = true;
    }

    public function saveCredentials(AuditService $auditService): void
    {
        $this->authorize('manageCredentials', $this->doctor);
        $this->resetMessages();

        $this->validate([
            'strNumber' => ['required', 'string', 'max:50'],
            'strValidUntil' => ['required', 'date'],
            'sipNumber' => ['required', 'string', 'max:50'],
            'sipValidUntil' => ['required', 'date'],
        ], [
            'strNumber.required' => 'Nomor STR wajib diisi.',
            'strValidUntil.required' => 'Masa berlaku STR wajib diisi.',
            'sipNumber.required' => 'Nomor SIP wajib diisi.',
            'sipValidUntil.required' => 'Masa berlaku SIP wajib diisi.',
        ]);

        $oldValues = [
            'str_number' => $this->doctor->str_number,
            'str_valid_until' => $this->doctor->str_valid_until?->toDateString(),
            'sip_number' => $this->doctor->sip_number,
            'sip_valid_until' => $this->doctor->sip_valid_until?->toDateString(),
        ];

        $this->doctor->update([
            'str_number' => $this->strNumber,
            'str_valid_until' => $this->strValidUntil,
            'sip_number' => $this->sipNumber,
            'sip_valid_until' => $this->sipValidUntil,
        ]);

        $auditService->log(
            action: 'doctor.credentials_updated',
            auditable: $this->doctor,
            oldValues: $oldValues,
            newValues: [
                'str_number' => $this->strNumber,
                'str_valid_until' => $this->strValidUntil,
                'sip_number' => $this->sipNumber,
                'sip_valid_until' => $this->sipValidUntil,
            ]
        );

        $this->showCredentialModal = false;
        $this->credFeedback = 'Kredensial STR dan SIP dokter berhasil diperbarui.';
    }

    // ==========================================
    // LINK TO ORGANIC EMPLOYEE
    // ==========================================

    public function openLinkModal(): void
    {
        $this->authorize('linkEmployee', $this->doctor);
        $this->resetMessages();
        $this->selectedEmployeeId = $this->doctor->employee_id;
        $this->showLinkModal = true;
    }

    public function saveLinkEmployee(AuditService $auditService): void
    {
        $this->authorize('linkEmployee', $this->doctor);
        $this->resetMessages();

        $this->validate([
            'selectedEmployeeId' => [
                'nullable',
                'exists:employees,id',
                Rule::unique('doctors', 'employee_id')->ignore($this->doctor->id),
            ],
        ], [
            'selectedEmployeeId.unique' => 'Pegawai ini sudah terhubung dengan dokter lain.',
        ]);

        $oldEmpId = $this->doctor->employee_id;
        $this->doctor->update([
            'employee_id' => $this->selectedEmployeeId ?: null,
        ]);

        $auditService->log(
            action: $this->selectedEmployeeId ? 'doctor.employee_linked' : 'doctor.employee_unlinked',
            auditable: $this->doctor,
            oldValues: ['employee_id' => $oldEmpId],
            newValues: ['employee_id' => $this->selectedEmployeeId]
        );

        $this->doctor->load('employee.unit', 'employee.position', 'employee.currentEmployment');
        $this->showLinkModal = false;
        $this->linkFeedback = $this->selectedEmployeeId
            ? 'Dokter berhasil dihubungkan dengan data pegawai organik RS.'
            : 'Tautan pegawai organik RS berhasil dilepas.';
    }

    // ==========================================
    // DOCUMENTS
    // ==========================================

    public function openDocModal(): void
    {
        $this->authorize('uploadDocument', $this->doctor);
        $this->resetMessages();
        $this->resetValidation();
        $this->docUploadType = 'str';
        $this->docUploadedFile = null;
        $this->showDocModal = true;
    }

    public function uploadDoc(DocumentService $documentService, AuditService $auditService): void
    {
        $this->authorize('uploadDocument', $this->doctor);
        $this->resetMessages();

        $this->validate([
            'docUploadType' => ['required', 'in:str,sip,certificate,ijazah,contract,other'],
            'docUploadedFile' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ], [
            'docUploadedFile.required' => 'Pilih berkas yang akan diunggah.',
            'docUploadedFile.max' => 'Ukuran berkas maksimal 10 MB.',
            'docUploadedFile.mimes' => 'Format berkas harus PDF, JPG, atau PNG.',
        ]);

        $originalName = $this->docUploadedFile->getClientOriginalName();
        $cleanName = trim(preg_replace('/[^\w\s\-\.]+/u', '', $this->doctor->name));
        $docCode = 'DOC-'.$this->doctor->id;
        $doctorFolder = "{$docCode} - {$cleanName}";
        $subFolder = strtoupper($this->docUploadType);
        $category = "Dokter/{$doctorFolder}/{$subFolder}";
        $customFileName = "{$subFolder}_{$originalName}";

        $stored = $documentService->upload(
            category: $category,
            file: $this->docUploadedFile,
            customFileName: $customFileName,
        );

        $document = Document::create([
            'documentable_type' => Doctor::class,
            'documentable_id' => $this->doctor->id,
            'document_type' => $this->docUploadType,
            'original_name' => $originalName,
            'storage_path' => $stored->fileIdentifier,
            'mime_type' => $stored->mimeType,
            'file_size' => $stored->sizeBytes,
            'storage_provider' => $stored->provider,
            'uploaded_by' => Auth::id(),
        ]);

        $auditService->log(
            action: 'document.uploaded',
            auditable: $document,
            newValues: [
                'doctor_id' => $this->doctor->id,
                'document_type' => $this->docUploadType,
                'original_name' => $originalName,
                'file_size' => $document->file_size,
            ]
        );

        $this->doctor->load('documents.uploadedBy');
        $this->showDocModal = false;
        $this->docUploadedFile = null;
        $this->docFeedback = "Dokumen berkas {$originalName} berhasil diunggah.";
    }

    public function deleteDocument(int $documentId, DocumentService $documentService, AuditService $auditService): void
    {
        $doc = Document::where('documentable_type', Doctor::class)
            ->where('documentable_id', $this->doctor->id)
            ->findOrFail($documentId);

        $this->authorize('delete', $doc);
        $this->resetMessages();

        try {
            $documentService->delete($doc->storage_path);
        } catch (\Throwable $e) {
            // Proceed with database deletion even if storage removal encounters warning
        }

        $auditService->log(
            action: 'document.deleted',
            auditable: $doc,
            oldValues: [
                'doctor_id' => $this->doctor->id,
                'document_type' => $doc->document_type,
                'original_name' => $doc->original_name,
            ]
        );

        $doc->delete();
        $this->doctor->load('documents.uploadedBy');
        $this->docFeedback = 'Dokumen berhasil dihapus dari arsip kredensial.';
    }

    // ==========================================
    // PORTAL USER ACCOUNT
    // ==========================================

    public function openAccountModal(): void
    {
        $this->authorize('update', $this->doctor);
        $this->resetMessages();
        $this->resetValidation();
        $this->syncFormData();
        $this->showAccountModal = true;
    }

    public function createPortalAccount(AuditService $auditService): void
    {
        $this->authorize('update', $this->doctor);
        $this->resetMessages();

        $this->validate([
            'accountEmail' => ['required', 'email', 'max:150', 'unique:users,email'],
        ], [
            'accountEmail.required' => 'Alamat email wajib diisi.',
            'accountEmail.email' => 'Format email tidak valid.',
            'accountEmail.unique' => 'Email ini sudah terdaftar sebagai akun pengguna sistem.',
        ]);

        $plainPassword = Str::random(10);

        DB::transaction(function () use ($plainPassword, $auditService) {
            $user = User::create([
                'name' => $this->doctor->name,
                'email' => $this->accountEmail,
                'password' => Hash::make($plainPassword),
                'is_active' => true,
            ]);

            // Assign doctor role
            $doctorRole = Role::firstOrCreate(['name' => 'doctor'], ['guard_name' => 'web']);
            $user->assignRole($doctorRole);

            $this->doctor->update(['user_id' => $user->id]);

            $auditService->log(
                action: 'doctor.account_created',
                auditable: $this->doctor,
                newValues: [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => 'doctor',
                ]
            );
        });

        $this->doctor->load('user.roles');
        $this->generatedPasswordNotice = "Akun portal berhasil dibuat! Password sementara: {$plainPassword}";
        $this->accountFeedback = 'Akun portal dokter berhasil diaktifkan.';
    }

    public function resetUserPassword(AuditService $auditService): void
    {
        $this->authorize('update', $this->doctor);
        $this->resetMessages();

        $this->validate([
            'newPassword' => ['required', 'string', 'min:8', 'same:newPasswordConfirmation'],
        ], [
            'newPassword.required' => 'Password baru wajib diisi.',
            'newPassword.min' => 'Password minimal 8 karakter.',
            'newPassword.same' => 'Konfirmasi password tidak cocok.',
        ]);

        if (! $this->doctor->user) {
            $this->accountError = 'Dokter belum memiliki akun pengguna.';

            return;
        }

        $this->doctor->user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $auditService->log(
            action: 'doctor.password_reset',
            auditable: $this->doctor->user,
            newValues: ['user_id' => $this->doctor->user->id]
        );

        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->accountFeedback = 'Password akun portal dokter berhasil diperbarui.';
    }

    public function toggleUserStatus(AuditService $auditService): void
    {
        $this->authorize('update', $this->doctor);
        $this->resetMessages();

        if (! $this->doctor->user) {
            return;
        }

        $newStatus = ! $this->doctor->user->is_active;
        $this->doctor->user->update(['is_active' => $newStatus]);
        $this->userIsActive = $newStatus;

        $auditService->log(
            action: 'doctor.account_status_changed',
            auditable: $this->doctor->user,
            newValues: ['is_active' => $newStatus]
        );

        $this->accountFeedback = $newStatus
            ? 'Akun login portal dokter telah diaktifkan.'
            : 'Akun login portal dokter telah dinonaktifkan.';
    }

    public function render()
    {
        $availableEmployees = Employee::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('id')
                    ->orWhereDoesntHave('doctor', function ($sub) {
                        $sub->where('id', '!=', $this->doctor->id);
                    });
            })
            ->orderBy('full_name')
            ->get();

        return view('livewire.doctor.doctor-show', [
            'availableEmployees' => $availableEmployees,
            'canManageCredentials' => Auth::user()?->can('manageCredentials', $this->doctor),
            'canUpdateBio' => Auth::user()?->can('update', $this->doctor),
            'canUploadDoc' => Auth::user()?->can('uploadDocument', $this->doctor),
            'canLinkEmployee' => Auth::user()?->can('linkEmployee', $this->doctor),
        ]);
    }
}
