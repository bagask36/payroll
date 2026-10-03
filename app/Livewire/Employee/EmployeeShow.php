<?php

namespace App\Livewire\Employee;

use App\Contracts\FaceAiService;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Services\Audit\AuditService;
use App\Services\Document\DocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Profil Pegawai - RS Juwita')]
class EmployeeShow extends Component
{
    use WithFileUploads;

    public Employee $employee;

    public string $activeTab = 'bio';

    public $photoUpload = null;

    public ?string $webcamCapture = null;

    public ?string $enrollStatusMessage = null;

    public ?string $enrollErrorMessage = null;

    // --- BIODATA MODAL STATE ---
    public bool $showBioModal = false;
    public string $bioFullName = '';
    public string $bioPhone = '';
    public string $bioAddress = '';
    public string $bioGender = 'male';
    public string $bioBirthPlace = '';
    public string $bioBirthDate = '';
    public string $bioMaritalStatus = 'single';
    public int $bioTaxDependents = 0;
    public ?string $bioNpwp = null;
    public ?string $bioKtp = null;
    public ?string $bioFeedback = null;
    public ?string $bioError = null;

    // --- BANK ACCOUNT MODAL STATE ---
    public bool $showBankModal = false;
    public ?int $editingBankId = null;
    public string $bankName = '';
    public string $accountNumber = '';
    public string $accountHolder = '';
    public bool $isPrimaryBank = true;
    public ?string $bankFeedback = null;
    public ?string $bankError = null;

    // --- DOCUMENT UPLOAD MODAL STATE ---
    public bool $showDocModal = false;
    public string $docUploadType = 'ktp';
    public $docUploadedFile = null;
    public ?string $docFeedback = null;
    public ?string $docError = null;

    // --- SALARY REVISION STATE ---
    public bool $showSalaryModal = false;
    public string $basicSalary = '';
    public string $salaryEffectiveFrom = '';
    public string $revisionReason = '';

    // --- SALARY COMPONENT STATE ---
    public bool $showComponentModal = false;
    public ?int $editingComponentAssignmentId = null;
    public ?int $selectedComponentId = null;
    public string $componentAmount = '';
    public string $componentEffectiveFrom = '';
    public ?string $componentEffectiveTo = null;
    public string $componentNotes = '';
    public bool $componentIsActive = true;

    public ?string $salaryFeedback = null;
    public ?string $salaryError = null;

    public function mount(int $id): void
    {
        $this->employee = Employee::with([
            'unit',
            'position',
            'employments',
            'salaries',
            'currentSalary',
            'salaryComponents.component',
            'bankAccounts',
            'documents',
        ])->findOrFail($id);

        $this->authorize('view', $this->employee);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetMessages();
    }

    public function resetMessages(): void
    {
        $this->enrollStatusMessage = null;
        $this->enrollErrorMessage = null;
        $this->salaryFeedback = null;
        $this->salaryError = null;
        $this->bioFeedback = null;
        $this->bioError = null;
        $this->bankFeedback = null;
        $this->bankError = null;
        $this->docFeedback = null;
        $this->docError = null;
    }

    // =========================================================
    // BIODATA
    // =========================================================

    public function openBioModal(): void
    {
        $this->authorize('update', $this->employee);
        $this->resetMessages();
        $this->resetValidation();

        $e = $this->employee;
        $this->bioFullName = (string) $e->full_name;
        $this->bioPhone = (string) $e->phone_number;
        $this->bioAddress = (string) $e->residential_address;
        $this->bioGender = (string) ($e->gender ?: 'male');
        $this->bioBirthPlace = (string) $e->birth_place;
        $this->bioBirthDate = $e->birth_date?->toDateString() ?? '';
        $this->bioMaritalStatus = (string) ($e->marital_status ?: 'single');
        $this->bioTaxDependents = (int) $e->tax_dependents;
        $this->bioNpwp = $e->npwp_number;
        // KTP only pre-filled for users allowed to see sensitive PII
        $this->bioKtp = Auth::user()?->can('viewSensitive', $e) ? $e->ktp_number : null;

        $this->showBioModal = true;
    }

    public function saveBio(AuditService $auditService): void
    {
        $this->authorize('update', $this->employee);
        $this->resetMessages();

        $canEditSensitive = (bool) Auth::user()?->can('viewSensitive', $this->employee);

        $rules = [
            'bioFullName' => ['required', 'string', 'max:150'],
            'bioPhone' => ['required', 'string', 'max:30'],
            'bioAddress' => ['required', 'string', 'max:500'],
            'bioGender' => ['required', 'in:male,female'],
            'bioBirthPlace' => ['required', 'string', 'max:100'],
            'bioBirthDate' => ['required', 'date', 'before:today'],
            'bioMaritalStatus' => ['required', 'in:single,married,divorced,widowed'],
            'bioTaxDependents' => ['required', 'integer', 'min:0', 'max:3'],
            'bioNpwp' => ['nullable', 'string', 'max:50'],
        ];
        if ($canEditSensitive) {
            $rules['bioKtp'] = ['required', 'digits:16'];
        }

        $this->validate($rules, [
            'bioFullName.required' => 'Nama lengkap wajib diisi.',
            'bioPhone.required' => 'Nomor telepon wajib diisi.',
            'bioAddress.required' => 'Alamat wajib diisi.',
            'bioBirthDate.before' => 'Tanggal lahir harus sebelum hari ini.',
            'bioTaxDependents.max' => 'Tanggungan PTKP maksimal 3 orang.',
            'bioKtp.digits' => 'NIK KTP harus 16 digit angka.',
        ]);

        $tracked = ['full_name', 'phone_number', 'residential_address', 'gender', 'birth_place', 'birth_date', 'marital_status', 'tax_dependents', 'npwp_number'];
        $old = collect($this->employee->only($tracked))
            ->map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v)
            ->all();

        $data = [
            'full_name' => $this->bioFullName,
            'phone_number' => $this->bioPhone,
            'residential_address' => $this->bioAddress,
            'gender' => $this->bioGender,
            'birth_place' => $this->bioBirthPlace,
            'birth_date' => $this->bioBirthDate,
            'marital_status' => $this->bioMaritalStatus,
            'tax_dependents' => $this->bioTaxDependents,
            'npwp_number' => $this->bioNpwp ?: null,
        ];

        $ktpChanged = false;
        if ($canEditSensitive && $this->bioKtp !== $this->employee->ktp_number) {
            $data['ktp_number'] = $this->bioKtp;
            $ktpChanged = true;
        }

        $this->employee->update($data);

        $new = collect($data)->except('ktp_number')->all();
        $auditService->log(
            action: 'employee.biodata_updated',
            auditable: $this->employee,
            oldValues: array_diff_assoc(array_map('strval', $old), array_map('strval', $new)),
            // Never log raw KTP; only flag that it changed
            newValues: array_merge(
                array_diff_assoc(array_map('strval', $new), array_map('strval', $old)),
                $ktpChanged ? ['ktp_number' => '[changed]'] : []
            )
        );

        $this->showBioModal = false;
        $this->employee->refresh();
        $this->bioFeedback = 'Biodata pegawai berhasil diperbarui.';
    }

    // =========================================================
    // BANK ACCOUNTS
    // =========================================================

    public function openBankModal(?int $id = null): void
    {
        $this->authorize('manageBank', $this->employee);
        $this->resetMessages();
        $this->resetValidation();

        if ($id) {
            $bank = EmployeeBankAccount::where('employee_id', $this->employee->id)->findOrFail($id);
            $this->editingBankId = $bank->id;
            $this->bankName = $bank->bank_name;
            $this->accountNumber = (string) $bank->account_number;
            $this->accountHolder = $bank->account_holder;
            $this->isPrimaryBank = (bool) $bank->is_primary;
        } else {
            $this->editingBankId = null;
            $this->bankName = '';
            $this->accountNumber = '';
            $this->accountHolder = (string) $this->employee->full_name;
            $this->isPrimaryBank = ! $this->employee->bankAccounts()->exists();
        }

        $this->showBankModal = true;
    }

    public function saveBank(AuditService $auditService): void
    {
        $this->authorize('manageBank', $this->employee);
        $this->resetMessages();

        $this->validate([
            'bankName' => ['required', 'string', 'max:50'],
            'accountNumber' => ['required', 'regex:/^[0-9]{5,30}$/'],
            'accountHolder' => ['required', 'string', 'max:150'],
            'isPrimaryBank' => ['boolean'],
        ], [
            'bankName.required' => 'Nama bank wajib diisi.',
            'accountNumber.required' => 'Nomor rekening wajib diisi.',
            'accountNumber.regex' => 'Nomor rekening hanya boleh berisi 5–30 digit angka.',
            'accountHolder.required' => 'Nama pemilik rekening wajib diisi.',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($auditService) {
            if ($this->isPrimaryBank) {
                EmployeeBankAccount::where('employee_id', $this->employee->id)
                    ->when($this->editingBankId, fn ($q) => $q->where('id', '!=', $this->editingBankId))
                    ->update(['is_primary' => false]);
            }

            $payload = [
                'bank_name' => $this->bankName,
                'account_number' => $this->accountNumber,
                'account_holder' => $this->accountHolder,
                'is_primary' => $this->isPrimaryBank,
            ];

            if ($this->editingBankId) {
                $bank = EmployeeBankAccount::where('employee_id', $this->employee->id)->findOrFail($this->editingBankId);
                $bank->update($payload);
                $action = 'employee.bank_account_updated';
            } else {
                $bank = EmployeeBankAccount::create($payload + ['employee_id' => $this->employee->id]);
                $action = 'employee.bank_account_added';
            }

            // Only masked account number goes to audit log
            $auditService->log(
                action: $action,
                auditable: $this->employee,
                oldValues: [],
                newValues: [
                    'bank_name' => $this->bankName,
                    'account_number' => '****'.substr($this->accountNumber, -4),
                    'is_primary' => $this->isPrimaryBank,
                ]
            );
        });

        $this->showBankModal = false;
        $this->employee->load('bankAccounts');
        $this->bankFeedback = 'Rekening payroll berhasil disimpan.';
    }

    public function deleteBank(int $id, AuditService $auditService): void
    {
        $this->authorize('manageBank', $this->employee);
        $this->resetMessages();

        $bank = EmployeeBankAccount::where('employee_id', $this->employee->id)->findOrFail($id);

        $auditService->log(
            action: 'employee.bank_account_deleted',
            auditable: $this->employee,
            oldValues: [
                'bank_name' => $bank->bank_name,
                'account_number' => '****'.substr((string) $bank->account_number, -4),
            ],
            newValues: []
        );

        $bank->delete();

        $this->employee->load('bankAccounts');
        $this->bankFeedback = 'Rekening payroll berhasil dihapus.';
    }

    // =========================================================
    // DOCUMENTS
    // =========================================================

    public function openDocModal(): void
    {
        $this->authorize('uploadDocument', $this->employee);
        $this->resetMessages();
        $this->resetValidation();
        $this->docUploadType = 'ktp';
        $this->docUploadedFile = null;
        $this->showDocModal = true;
    }

    public function uploadDoc(DocumentService $documentService, AuditService $auditService): void
    {
        $this->authorize('uploadDocument', $this->employee);
        $this->resetMessages();

        $this->validate([
            'docUploadType' => ['required', 'in:ktp,kk,ijazah,contract,str,sip,certificate,other'],
            'docUploadedFile' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ], [
            'docUploadedFile.required' => 'Pilih berkas yang akan diunggah.',
            'docUploadedFile.max' => 'Ukuran berkas maksimal 10 MB.',
            'docUploadedFile.mimes' => 'Format berkas harus PDF, JPG, atau PNG.',
        ]);

        $originalName = $this->docUploadedFile->getClientOriginalName();

        $stored = $documentService->upload(
            category: 'compliance/'.$this->docUploadType,
            file: $this->docUploadedFile,
            customFileName: $originalName,
        );

        $document = Document::create([
            'documentable_type' => Employee::class,
            'documentable_id' => $this->employee->id,
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
                'employee_id' => $this->employee->id,
                'document_type' => $this->docUploadType,
                'original_name' => $originalName,
                'file_size' => $document->file_size,
            ]
        );

        $this->showDocModal = false;
        $this->docUploadedFile = null;
        $this->employee->load('documents');
        $this->docFeedback = 'Berkas berhasil diunggah dan diarsipkan.';
    }

    public function deleteDoc(int $id, AuditService $auditService): void
    {
        $doc = $this->employee->documents()->findOrFail($id);
        $this->authorize('delete', $doc);
        $this->resetMessages();

        $auditService->log(
            action: 'document.deleted',
            auditable: $doc,
            oldValues: [
                'employee_id' => $this->employee->id,
                'document_type' => $doc->document_type,
                'original_name' => $doc->original_name,
            ],
            newValues: []
        );

        // Soft-delete metadata only; physical file retained for audit/retention
        $doc->delete();

        $this->employee->load('documents');
        $this->docFeedback = 'Berkas berhasil dihapus dari arsip.';
    }

    public function openSalaryModal(): void
    {
        $this->authorize('updateSalary', $this->employee);
        $this->resetMessages();

        $current = $this->employee->currentSalary ?? $this->employee->salaries()->latest('effective_from')->first();
        $this->basicSalary = $current ? (string) (float) $current->basic_salary : '';
        $this->salaryEffectiveFrom = Carbon::today()->toDateString();
        $this->revisionReason = '';
        $this->showSalaryModal = true;
    }

    public function saveSalary(AuditService $auditService): void
    {
        $this->authorize('updateSalary', $this->employee);
        $this->resetMessages();

        $this->validate([
            'basicSalary' => ['required', 'numeric', 'min:0'],
            'salaryEffectiveFrom' => ['required', 'date'],
            'revisionReason' => ['required', 'string', 'max:255'],
        ], [
            'basicSalary.required' => 'Gaji pokok wajib diisi.',
            'basicSalary.numeric' => 'Gaji pokok harus berupa angka.',
            'salaryEffectiveFrom.required' => 'Tanggal efektif wajib diisi.',
            'revisionReason.required' => 'Alasan penetapan/revisi wajib diisi.',
        ]);

        $previous = $this->employee->currentSalary;
        if ($previous) {
            $previousEffectiveTo = Carbon::parse($this->salaryEffectiveFrom)->subDay()->toDateString();
            if ($previous->effective_from <= $previousEffectiveTo) {
                $previous->update(['effective_to' => $previousEffectiveTo]);
            }
        }

        $salary = EmployeeSalary::create([
            'employee_id' => $this->employee->id,
            'basic_salary' => $this->basicSalary,
            'effective_from' => $this->salaryEffectiveFrom,
            'effective_to' => null,
            'revision_reason' => $this->revisionReason,
            'created_by' => Auth::id() ?? 1,
        ]);

        $auditService->log(
            action: 'employee.salary_revision',
            auditable: $this->employee,
            oldValues: ['basic_salary' => $previous?->basic_salary],
            newValues: [
                'basic_salary' => (float) $this->basicSalary,
                'effective_from' => $this->salaryEffectiveFrom,
                'revision_reason' => $this->revisionReason,
            ]
        );

        $this->showSalaryModal = false;
        $this->employee->load(['salaries', 'currentSalary']);
        $this->salaryFeedback = 'Gaji pokok pegawai berhasil diperbarui dengan tanggal efektif ' . Carbon::parse($this->salaryEffectiveFrom)->format('d/m/Y') . '.';
    }

    public function openComponentModal(?int $id = null): void
    {
        $this->authorize('updateSalary', $this->employee);
        $this->resetMessages();

        if ($id) {
            $assignment = EmployeeSalaryComponent::where('employee_id', $this->employee->id)->findOrFail($id);
            $this->editingComponentAssignmentId = $assignment->id;
            $this->selectedComponentId = $assignment->salary_component_id;
            $this->componentAmount = (string) (float) $assignment->amount;
            $this->componentEffectiveFrom = $assignment->effective_from?->toDateString() ?? Carbon::today()->toDateString();
            $this->componentEffectiveTo = $assignment->effective_to?->toDateString();
            $this->componentNotes = $assignment->notes ?? '';
            $this->componentIsActive = (bool) $assignment->is_active;
        } else {
            $this->editingComponentAssignmentId = null;
            $this->selectedComponentId = null;
            $this->componentAmount = '';
            $this->componentEffectiveFrom = Carbon::today()->toDateString();
            $this->componentEffectiveTo = null;
            $this->componentNotes = '';
            $this->componentIsActive = true;
        }

        $this->showComponentModal = true;
    }

    public function updatedSelectedComponentId($value): void
    {
        if ($value && empty($this->componentAmount)) {
            $comp = SalaryComponent::find($value);
            if ($comp && (float) $comp->default_amount > 0) {
                $this->componentAmount = (string) (float) $comp->default_amount;
            }
        }
    }

    public function saveComponent(AuditService $auditService): void
    {
        $this->authorize('updateSalary', $this->employee);
        $this->resetMessages();

        $this->validate([
            'selectedComponentId' => ['required', 'exists:salary_components,id'],
            'componentAmount' => ['required', 'numeric', 'min:0'],
            'componentEffectiveFrom' => ['required', 'date'],
            'componentEffectiveTo' => ['nullable', 'date', 'after_or_equal:componentEffectiveFrom'],
            'componentNotes' => ['nullable', 'string', 'max:255'],
            'componentIsActive' => ['boolean'],
        ], [
            'selectedComponentId.required' => 'Pilih komponen gaji.',
            'componentAmount.required' => 'Nominal komponen wajib diisi.',
            'componentAmount.numeric' => 'Nominal harus berupa angka.',
            'componentEffectiveFrom.required' => 'Tanggal berlaku wajib diisi.',
            'componentEffectiveTo.after_or_equal' => 'Tanggal berakhir harus sama atau setelah tanggal berlaku.',
        ]);

        $component = SalaryComponent::findOrFail($this->selectedComponentId);

        if ($this->editingComponentAssignmentId) {
            $assignment = EmployeeSalaryComponent::where('employee_id', $this->employee->id)
                ->findOrFail($this->editingComponentAssignmentId);

            $oldData = $assignment->only(['amount', 'is_active', 'effective_from', 'effective_to']);

            $assignment->update([
                'salary_component_id' => $this->selectedComponentId,
                'amount' => $this->componentAmount,
                'effective_from' => $this->componentEffectiveFrom,
                'effective_to' => $this->componentEffectiveTo ?: null,
                'notes' => $this->componentNotes ?: null,
                'is_active' => $this->componentIsActive,
            ]);

            $auditService->log(
                action: 'employee.salary_component_updated',
                auditable: $this->employee,
                oldValues: $oldData,
                newValues: [
                    'component' => $component->name,
                    'amount' => (float) $this->componentAmount,
                    'is_active' => $this->componentIsActive,
                ]
            );

            $this->salaryFeedback = "Komponen {$component->name} berhasil diperbarui.";
        } else {
            $assignment = EmployeeSalaryComponent::create([
                'employee_id' => $this->employee->id,
                'salary_component_id' => $this->selectedComponentId,
                'amount' => $this->componentAmount,
                'effective_from' => $this->componentEffectiveFrom,
                'effective_to' => $this->componentEffectiveTo ?: null,
                'notes' => $this->componentNotes ?: null,
                'is_active' => $this->componentIsActive,
                'created_by' => Auth::id() ?? 1,
            ]);

            $auditService->log(
                action: 'employee.salary_component_assigned',
                auditable: $this->employee,
                oldValues: [],
                newValues: [
                    'component' => $component->name,
                    'amount' => (float) $this->componentAmount,
                    'effective_from' => $this->componentEffectiveFrom,
                ]
            );

            $this->salaryFeedback = "Komponen {$component->name} berhasil ditambahkan ke pegawai.";
        }

        $this->showComponentModal = false;
        $this->employee->load(['salaryComponents.component', 'activeSalaryComponents.component']);
    }

    public function deleteComponent(int $id, AuditService $auditService): void
    {
        $this->authorize('updateSalary', $this->employee);
        $this->resetMessages();

        $assignment = EmployeeSalaryComponent::where('employee_id', $this->employee->id)->findOrFail($id);
        $componentName = $assignment->component?->name ?? 'Komponen';

        $auditService->log(
            action: 'employee.salary_component_deleted',
            auditable: $this->employee,
            oldValues: [
                'component' => $componentName,
                'amount' => (float) $assignment->amount,
            ],
            newValues: []
        );

        $assignment->delete();

        $this->employee->load(['salaryComponents.component', 'activeSalaryComponents.component']);
        $this->salaryFeedback = "Komponen {$componentName} berhasil dihapus dari pegawai.";
    }


    public function enrollBiometrics(FaceAiService $faceAiService, AuditService $auditService): void
    {
        $this->authorize('manageBiometrics', $this->employee);
        $this->resetMessages();

        $imageContent = null;

        if ($this->photoUpload) {
            $this->validate([
                'photoUpload' => 'image|max:5120',
            ]);
            $imageContent = file_get_contents($this->photoUpload->getRealPath());
        } elseif ($this->webcamCapture) {
            $imageContent = $this->webcamCapture;
        }

        if (empty($imageContent)) {
            $this->enrollErrorMessage = 'Silakan ambil foto wajah dengan kamera atau unggah berkas foto.';

            return;
        }

        // 1. Liveness check
        $liveness = $faceAiService->checkLiveness($imageContent);
        if (! $liveness->isLive) {
            $this->enrollErrorMessage = 'Deteksi keaslian wajah gagal (kemungkinan foto/spoofing): '.$liveness->message;

            return;
        }

        // 2. Extract feature vector
        $embeddingResult = $faceAiService->extractEmbedding($imageContent);
        if (! $embeddingResult->success) {
            $this->enrollErrorMessage = 'Ekstraksi fitur biometrik gagal: '.$embeddingResult->message;

            return;
        }

        $hadPrevious = ! empty($this->employee->face_biometric_vector);

        // 3. Save encrypted vector
        $this->employee->face_biometric_vector = $embeddingResult->embedding;
        $this->employee->save();

        // 4. Audit trail
        $auditService->log(
            action: 'employee.biometrics_enrolled',
            auditable: $this->employee,
            oldValues: ['has_biometric' => $hadPrevious],
            newValues: [
                'has_biometric' => true,
                'vector_dimensions' => count($embeddingResult->embedding),
                'engine' => $embeddingResult->metadata['engine'] ?? 'local_mock',
            ]
        );

        $this->photoUpload = null;
        $this->webcamCapture = null;
        $this->employee->refresh();
        $this->enrollStatusMessage = 'Biometrik wajah berhasil didaftarkan dan dienkripsi di basis data.';
    }

    public function removeBiometrics(AuditService $auditService): void
    {
        $this->authorize('manageBiometrics', $this->employee);
        $this->resetMessages();

        $this->employee->face_biometric_vector = null;
        $this->employee->save();

        $auditService->log(
            action: 'employee.biometrics_removed',
            auditable: $this->employee,
            oldValues: ['has_biometric' => true],
            newValues: ['has_biometric' => false]
        );

        $this->employee->refresh();
        $this->enrollStatusMessage = 'Data biometrik wajah pegawai berhasil dihapus.';
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.employee.employee-show', [
            'canViewSensitive' => $user?->can('viewSensitive', $this->employee),
            'canViewSalary' => $user?->can('viewSalary', $this->employee),
            'canUpdateSalary' => $user?->can('updateSalary', $this->employee),
            'canViewBank' => $user?->can('viewBank', $this->employee),
            'canManageBiometrics' => $user?->can('manageBiometrics', $this->employee),
            'canUpdateBio' => $user?->can('update', $this->employee),
            'canManageBank' => $user?->can('manageBank', $this->employee),
            'canUploadDoc' => $user?->can('uploadDocument', $this->employee),
            'availableComponents' => SalaryComponent::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'assignedComponents' => $this->employee->salaryComponents()->with('component')->latest('effective_from')->get(),
        ]);
    }
}
