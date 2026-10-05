<?php

namespace App\Livewire\Employee;

use App\Contracts\FaceAiService;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeWarningLetter;
use App\Models\FaceBiometricRegistration;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\Unit;
use App\Models\WarningLetterType;
use App\Services\Audit\AuditService;
use App\Services\Document\DocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    // --- FACE REGISTRATION REVIEW STATE ---
    public ?int $selectedRegistrationId = null;

    public string $registrationRejectionReason = '';

    public bool $showRejectRegistrationModal = false;

    // --- BIODATA MODAL STATE ---
    public bool $showBioModal = false;

    public string $bioFullName = '';

    public string $bioEmail = '';

    public string $bioPhone = '';

    public string $bioAddress = '';

    public string $bioGender = 'male';

    public string $bioBirthPlace = '';

    public string $bioBirthDate = '';

    public string $bioMaritalStatus = 'single';

    public string $bioReligion = 'islam';

    public int $bioTaxDependents = 0;

    public ?string $bioEducationLevel = 'S1';

    public string $bioScheduleType = 'non_shift';

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

    // --- EMPLOYMENT / ASSIGNMENT MODAL STATE ---
    public bool $showEmploymentModal = false;

    public ?int $employmentUnitId = null;

    public ?int $employmentPositionId = null;

    public string $employmentContractType = 'permanent';

    public string $employmentScheduleType = 'non_shift';

    public string $employmentContractNumber = '';

    public string $employmentStartDate = '';

    public ?string $employmentEndDate = null;

    public string $employmentNotes = '';

    public bool $syncSalaryWithSk = true;

    public ?string $employmentBasicSalary = '';

    public ?string $employmentPositionalAllowance = '';

    public ?int $editingEmploymentId = null;

    public $employmentDocumentFile = null;

    public ?int $employmentExistingDocumentId = null;

    public ?string $employmentExistingDocumentName = null;

    public ?string $employmentFeedback = null;

    public ?string $employmentError = null;

    // --- WARNING LETTERS & DISCIPLINARY STATE ---
    public bool $showWarningLetterModal = false;

    public ?int $editingWarningLetterId = null;

    public ?int $spTypeId = null;

    public string $spLetterNumber = '';

    public string $spIssuedDate = '';

    public string $spValidFrom = '';

    public string $spValidUntil = '';

    public string $spViolationReason = '';

    public string $spAuthorizedBy = '';

    public string $spNotes = '';

    public $spDocumentFile = null;

    public ?string $spFeedback = null;

    public ?string $spError = null;

    // Revocation Modal State
    public bool $showRevokeModal = false;

    public ?int $revokingWarningLetterId = null;

    public string $revocationReason = '';

    public function mount(int $id): void
    {
        $this->employee = Employee::with([
            'unit',
            'position',
            'employments.unit',
            'employments.position',
            'employments.document',
            'salaries',
            'currentSalary',
            'salaryComponents.component',
            'bankAccounts',
            'documents',
            'warningLetters.warningLetterType',
            'warningLetters.revoker',
            'faceBiometricRegistrations.requestedBy',
            'faceBiometricRegistrations.reviewedBy',
        ])->findOrFail($id);

        if (request()->has('tab') && in_array(request()->query('tab'), ['bio', 'salary', 'employment', 'bank', 'documents', 'biometrics', 'disciplinary'])) {
            $this->activeTab = (string) request()->query('tab');
        }

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
        $this->employmentFeedback = null;
        $this->employmentError = null;
        $this->spFeedback = null;
        $this->spError = null;
    }

    // =========================================================
    // RIWAYAT PENUGASAN & IKATAN KERJA (EMPLOYMENT)
    // =========================================================

    public function updatedEmploymentPositionId(?int $positionId): void
    {
        if ($positionId) {
            $pos = Position::with('document')->find($positionId);
            if ($pos) {
                if ($pos->unit_id && empty($this->employmentUnitId)) {
                    $this->employmentUnitId = $pos->unit_id;
                }
                if ($pos->default_basic_salary !== null) {
                    $this->employmentBasicSalary = (string) (float) $pos->default_basic_salary;
                }
                if ($pos->default_positional_allowance !== null) {
                    $this->employmentPositionalAllowance = (string) (float) $pos->default_positional_allowance;
                }
                if ($pos->default_contract_number) {
                    $this->employmentContractNumber = $pos->default_contract_number;
                }
                if ($pos->document_id && ! $this->employmentDocumentFile) {
                    $this->employmentExistingDocumentId = $pos->document_id;
                    $this->employmentExistingDocumentName = ($pos->document?->original_name ?? 'Berkas SK Formasi').' (Standar SK Jabatan)';
                }
            }
        }
    }

    public function pullSkFromPosition(): void
    {
        if (! $this->employmentPositionId) {
            return;
        }

        $pos = Position::with('document')->find($this->employmentPositionId);
        if (! $pos) {
            return;
        }

        if ($pos->default_contract_number) {
            $this->employmentContractNumber = $pos->default_contract_number;
        }
        if ($pos->document_id) {
            $this->employmentExistingDocumentId = $pos->document_id;
            $this->employmentExistingDocumentName = ($pos->document?->original_name ?? 'Berkas SK Formasi').' (Standar SK Jabatan)';
            $this->employmentDocumentFile = null;
        }
        if ($pos->default_basic_salary !== null) {
            $this->employmentBasicSalary = (string) (float) $pos->default_basic_salary;
        }
        if ($pos->default_positional_allowance !== null) {
            $this->employmentPositionalAllowance = (string) (float) $pos->default_positional_allowance;
        }

        $this->dispatch('notify', variant: 'success', message: 'Nomor SK, berkas fisik SK, dan remunerasi berhasil ditarik dari jabatan '.$pos->title);
    }

    public function applyPositionDefaultSalary(): void
    {
        $this->employee->load('position');
        if ($this->employee->position?->default_basic_salary !== null) {
            $this->basicSalary = (string) (float) $this->employee->position->default_basic_salary;
            $this->revisionReason = 'Standar SK Jabatan '.$this->employee->position->title;
        }
    }

    public function openEmploymentModal(): void
    {
        $this->authorize('updateEmployment', $this->employee);
        $this->resetMessages();
        $this->resetValidation();

        $latestEmployment = $this->employee->currentEmployment;

        $this->editingEmploymentId = null;
        $this->employmentDocumentFile = null;
        $this->employmentExistingDocumentId = null;
        $this->employmentExistingDocumentName = null;

        $this->employmentUnitId = $this->employee->unit_id;
        $this->employmentPositionId = $this->employee->position_id;
        $this->employmentContractType = $latestEmployment?->contract_type ?? 'permanent';
        $this->employmentScheduleType = $this->employee->schedule_type ?? 'non_shift';
        $this->employmentContractNumber = 'SK/RSJ/'.Carbon::now()->format('Ym').'/'.str_pad((string) $this->employee->id, 4, '0', STR_PAD_LEFT);
        $this->employmentStartDate = Carbon::today()->toDateString();
        $this->employmentEndDate = null;
        $this->employmentNotes = '';
        $this->syncSalaryWithSk = true;

        $currentPos = $this->employee->position;
        if ($currentPos) {
            $currentPos->load('document');
            if ($currentPos->default_contract_number) {
                $this->employmentContractNumber = $currentPos->default_contract_number;
            }
            if ($currentPos->document_id) {
                $this->employmentExistingDocumentId = $currentPos->document_id;
                $this->employmentExistingDocumentName = ($currentPos->document?->original_name ?? 'Berkas SK Formasi').' (Standar SK Jabatan)';
            }
        }

        $activeSalary = $this->employee->currentSalary;

        $this->employmentBasicSalary = $currentPos?->default_basic_salary !== null
            ? (string) (float) $currentPos->default_basic_salary
            : ($activeSalary ? (string) (float) $activeSalary->basic_salary : '');

        $this->employmentPositionalAllowance = $currentPos?->default_positional_allowance !== null
            ? (string) (float) $currentPos->default_positional_allowance
            : '';

        $this->showEmploymentModal = true;
    }

    public function editEmployment(int $id): void
    {
        $this->authorize('updateEmployment', $this->employee);
        $this->resetMessages();
        $this->resetValidation();

        $rec = $this->employee->employments()->with('document')->findOrFail($id);

        $this->editingEmploymentId = $rec->id;
        $this->employmentUnitId = $rec->unit_id;
        $this->employmentPositionId = $rec->position_id;
        $this->employmentContractType = $rec->contract_type;
        $this->employmentScheduleType = $rec->schedule_type ?? ($rec->is_active ? ($this->employee->schedule_type ?? 'non_shift') : 'non_shift');
        $this->employmentContractNumber = $rec->contract_number;
        $this->employmentStartDate = $rec->start_date?->toDateString() ?? '';
        $this->employmentEndDate = $rec->end_date?->toDateString();
        $this->employmentNotes = $rec->notes ?? '';
        $this->syncSalaryWithSk = false;
        $this->employmentDocumentFile = null;
        $this->employmentExistingDocumentId = $rec->document_id;
        $this->employmentExistingDocumentName = $rec->document?->original_name;

        $pos = $rec->position;
        $this->employmentBasicSalary = $pos?->default_basic_salary !== null ? (string) (float) $pos->default_basic_salary : '';
        $this->employmentPositionalAllowance = $pos?->default_positional_allowance !== null ? (string) (float) $pos->default_positional_allowance : '';

        $this->showEmploymentModal = true;
    }

    public function downloadEmploymentDocument(int $employmentId, DocumentService $documentService)
    {
        $employment = $this->employee->employments()->with('document')->findOrFail($employmentId);
        if (! $employment->document) {
            abort(404, 'Berkas fisik SK belum diunggah.');
        }

        $this->authorize('download', $employment->document);

        $content = $documentService->download($employment->document->storage_path);
        if ($content === null) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan server.');
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $employment->document->original_name, [
            'Content-Type' => $employment->document->mime_type,
        ]);
    }

    public function saveEmployment(AuditService $auditService, DocumentService $documentService): void
    {
        $this->authorize('updateEmployment', $this->employee);
        $this->resetMessages();

        $canUpdateSalary = Auth::user()?->can('updateSalary', $this->employee);

        $rules = [
            'employmentUnitId' => ['required', 'exists:units,id'],
            'employmentPositionId' => ['required', 'exists:positions,id'],
            'employmentContractType' => ['required', 'in:permanent,contract,probation,internship'],
            'employmentScheduleType' => ['required', 'in:non_shift,shift'],
            'employmentContractNumber' => ['required', 'string', 'max:100'],
            'employmentStartDate' => ['required', 'date'],
            'employmentEndDate' => ['nullable', 'date', 'after_or_equal:employmentStartDate'],
            'employmentNotes' => ['nullable', 'string', 'max:500'],
            'employmentDocumentFile' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];

        if ($this->syncSalaryWithSk && $canUpdateSalary) {
            $rules['employmentBasicSalary'] = ['nullable', 'numeric', 'min:0'];
            $rules['employmentPositionalAllowance'] = ['nullable', 'numeric', 'min:0'];
        }

        $this->validate($rules, [
            'employmentUnitId.required' => 'Unit penempatan wajib dipilih.',
            'employmentPositionId.required' => 'Jabatan wajib dipilih.',
            'employmentContractType.required' => 'Jenis ikatan kerja wajib dipilih.',
            'employmentScheduleType.required' => 'Pola jam kerja wajib dipilih.',
            'employmentContractNumber.required' => 'Nomor SK / Kontrak wajib diisi.',
            'employmentStartDate.required' => 'Tanggal mulai tugas wajib diisi.',
            'employmentEndDate.after_or_equal' => 'Tanggal akhir kontrak harus sama atau setelah tanggal mulai.',
            'employmentBasicSalary.numeric' => 'Nominal gaji pokok SK harus berupa angka.',
            'employmentPositionalAllowance.numeric' => 'Nominal tunjangan jabatan SK harus berupa angka.',
            'employmentDocumentFile.max' => 'Ukuran berkas SK maksimal 10 MB.',
            'employmentDocumentFile.mimes' => 'Format berkas SK harus berupa PDF, JPG, atau PNG.',
        ]);

        $uploadedDocId = $this->employmentExistingDocumentId;
        if ($this->employmentDocumentFile) {
            $originalName = $this->employmentDocumentFile->getClientOriginalName();
            $empCode = $this->employee->nik ?: ($this->employee->employee_id ?: 'EMP'.$this->employee->id);
            $cleanName = trim(preg_replace('/[^\w\s\-\.]+/u', '', $this->employee->name));
            $empFolder = "{$empCode} - {$cleanName}";
            $cleanContractNumber = trim(preg_replace('/[^\w\-]+/u', '_', $this->employmentContractNumber));
            $customFileName = "SK_Kontrak_{$cleanContractNumber}_{$originalName}";

            $stored = $documentService->upload(
                category: "Pegawai/{$empFolder}/SK_Kontrak",
                file: $this->employmentDocumentFile,
                customFileName: $customFileName,
            );

            $doc = Document::create([
                'documentable_type' => Employee::class,
                'documentable_id' => $this->employee->id,
                'document_type' => 'contract',
                'original_name' => $originalName,
                'storage_path' => $stored->fileIdentifier,
                'mime_type' => $stored->mimeType,
                'file_size' => $stored->sizeBytes,
                'storage_provider' => $stored->provider,
                'uploaded_by' => Auth::id() ?? 1,
            ]);

            $uploadedDocId = $doc->id;
        }

        if ($this->editingEmploymentId) {
            // Edit & Koreksi riwayat penugasan yang sudah ada (masa lalu maupun aktif)
            $empRec = EmployeeEmployment::where('employee_id', $this->employee->id)->findOrFail($this->editingEmploymentId);
            $oldValues = [
                'unit_id' => $empRec->unit_id,
                'position_id' => $empRec->position_id,
                'contract_type' => $empRec->contract_type,
                'schedule_type' => $empRec->schedule_type ?? 'non_shift',
                'contract_number' => $empRec->contract_number,
                'start_date' => $empRec->start_date?->toDateString(),
                'end_date' => $empRec->end_date?->toDateString(),
            ];

            DB::transaction(function () use ($empRec, $uploadedDocId, $canUpdateSalary) {
                $empRec->update([
                    'unit_id' => $this->employmentUnitId,
                    'position_id' => $this->employmentPositionId,
                    'contract_type' => $this->employmentContractType,
                    'schedule_type' => $this->employmentScheduleType,
                    'contract_number' => $this->employmentContractNumber,
                    'start_date' => $this->employmentStartDate,
                    'end_date' => $this->employmentEndDate ?: null,
                    'notes' => $this->employmentNotes ?: null,
                    'document_id' => $uploadedDocId,
                ]);

                // Jika yang diedit merupakan penugasan aktif, sinkronkan profil utama pegawai
                if ($empRec->is_active) {
                    $this->employee->update([
                        'unit_id' => $this->employmentUnitId,
                        'position_id' => $this->employmentPositionId,
                        'schedule_type' => $this->employmentScheduleType,
                    ]);
                }

                if ($this->syncSalaryWithSk && $canUpdateSalary) {
                    $this->applyRemunerationFromSk();
                }
            });

            $auditService->log(
                action: 'employee.assignment_updated',
                auditable: $this->employee,
                oldValues: $oldValues,
                newValues: [
                    'unit_id' => $this->employmentUnitId,
                    'position_id' => $this->employmentPositionId,
                    'contract_type' => $this->employmentContractType,
                    'schedule_type' => $this->employmentScheduleType,
                    'contract_number' => $this->employmentContractNumber,
                    'start_date' => $this->employmentStartDate,
                    'has_document' => (bool) $uploadedDocId,
                ]
            );

            $this->employmentFeedback = 'Data SK penugasan berhasil disesuaikan dan diperbarui.';
        } else {
            // Catat penugasan baru (Mendukung tanggal berlaku surut / retroactive)
            $oldUnitId = $this->employee->unit_id;
            $oldPositionId = $this->employee->position_id;
            $oldContractType = $this->employee->currentEmployment?->contract_type;
            $oldScheduleType = $this->employee->schedule_type ?? 'non_shift';

            DB::transaction(function () use ($uploadedDocId, $canUpdateSalary) {
                $startDate = Carbon::parse($this->employmentStartDate);
                $prevEndDate = $startDate->copy()->subDay()->toDateString();

                // Non-aktifkan penugasan aktif sebelumnya dan batasi tanggal akhirnya
                EmployeeEmployment::where('employee_id', $this->employee->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'end_date' => $prevEndDate,
                    ]);

                // Buat record riwayat penugasan baru
                EmployeeEmployment::create([
                    'employee_id' => $this->employee->id,
                    'unit_id' => $this->employmentUnitId,
                    'position_id' => $this->employmentPositionId,
                    'contract_type' => $this->employmentContractType,
                    'schedule_type' => $this->employmentScheduleType,
                    'contract_number' => $this->employmentContractNumber,
                    'start_date' => $this->employmentStartDate,
                    'end_date' => $this->employmentEndDate ?: null,
                    'notes' => $this->employmentNotes ?: null,
                    'document_id' => $uploadedDocId,
                    'is_active' => true,
                ]);

                // Otomatis mutasikan / perbarui unit, jabatan, dan pola jam kerja aktif di profil pegawai
                $this->employee->update([
                    'unit_id' => $this->employmentUnitId,
                    'position_id' => $this->employmentPositionId,
                    'schedule_type' => $this->employmentScheduleType,
                ]);

                // Sinkronkan remunerasi standar jika opsi aktif dan user berwenang
                if ($this->syncSalaryWithSk && $canUpdateSalary) {
                    $this->applyRemunerationFromSk();
                }
            });

            $auditService->log(
                action: 'employee.assignment_recorded',
                auditable: $this->employee,
                oldValues: [
                    'unit_id' => $oldUnitId,
                    'position_id' => $oldPositionId,
                    'contract_type' => $oldContractType,
                    'schedule_type' => $oldScheduleType,
                ],
                newValues: [
                    'unit_id' => $this->employmentUnitId,
                    'position_id' => $this->employmentPositionId,
                    'contract_type' => $this->employmentContractType,
                    'schedule_type' => $this->employmentScheduleType,
                    'contract_number' => $this->employmentContractNumber,
                    'start_date' => $this->employmentStartDate,
                    'notes' => $this->employmentNotes,
                    'has_document' => (bool) $uploadedDocId,
                    'sync_salary' => $this->syncSalaryWithSk && $canUpdateSalary,
                ]
            );

            $this->employmentFeedback = 'Riwayat penugasan/SK baru berhasil dicatat dan status pegawai telah diperbarui.';
        }

        $this->showEmploymentModal = false;
        $this->editingEmploymentId = null;
        $this->employmentDocumentFile = null;

        $this->employee->refresh();
        $this->employee->load([
            'unit',
            'position',
            'employments.unit',
            'employments.position',
            'employments.document',
            'currentEmployment',
            'currentSalary',
            'salaries',
            'salaryComponents.component',
            'activeSalaryComponents.component',
        ]);
    }

    protected function applyRemunerationFromSk(): void
    {
        $pos = Position::find($this->employmentPositionId);
        $posTitle = $pos?->title ?? 'Jabatan';

        // 1. Gaji Pokok
        if ($this->employmentBasicSalary !== '' && $this->employmentBasicSalary !== null && (float) $this->employmentBasicSalary > 0) {
            $previous = $this->employee->currentSalary;
            if ($previous) {
                $previousEffectiveTo = Carbon::parse($this->employmentStartDate)->subDay()->toDateString();
                if ($previous->effective_from <= $previousEffectiveTo) {
                    $previous->update(['effective_to' => $previousEffectiveTo]);
                }
            }

            EmployeeSalary::create([
                'employee_id' => $this->employee->id,
                'basic_salary' => $this->employmentBasicSalary,
                'effective_from' => $this->employmentStartDate,
                'effective_to' => null,
                'revision_reason' => "SK Penetapan Jabatan {$posTitle} (No. {$this->employmentContractNumber})",
                'created_by' => Auth::id() ?? 1,
            ]);
        }

        // 2. Tunjangan Jabatan (TUNJ_JABATAN)
        if ($this->employmentPositionalAllowance !== '' && $this->employmentPositionalAllowance !== null) {
            $tunjJabatanComp = SalaryComponent::where('code', 'TUNJ_JABATAN')->first()
                ?? SalaryComponent::where('type', 'allowance')->where('name', 'like', '%Jabatan%')->first();

            if ($tunjJabatanComp) {
                EmployeeSalaryComponent::where('employee_id', $this->employee->id)
                    ->where('salary_component_id', $tunjJabatanComp->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'effective_to' => Carbon::parse($this->employmentStartDate)->subDay()->toDateString(),
                    ]);

                if ((float) $this->employmentPositionalAllowance > 0) {
                    EmployeeSalaryComponent::create([
                        'employee_id' => $this->employee->id,
                        'salary_component_id' => $tunjJabatanComp->id,
                        'amount' => $this->employmentPositionalAllowance,
                        'effective_from' => $this->employmentStartDate,
                        'effective_to' => null,
                        'notes' => "SK Penetapan Jabatan (No. {$this->employmentContractNumber})",
                        'is_active' => true,
                        'created_by' => Auth::id() ?? 1,
                    ]);
                }
            }
        }
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
        $canSensitive = (bool) Auth::user()?->can('viewSensitive', $e);
        $this->bioFullName = (string) $e->full_name;
        $this->bioEmail = (string) ($e->email !== '-' ? $e->email : '');
        $this->bioPhone = (string) $e->phone_number;
        $this->bioAddress = $canSensitive ? (string) $e->residential_address : '';
        $this->bioGender = (string) ($e->gender ?: 'male');
        $this->bioBirthPlace = (string) $e->birth_place;
        $this->bioBirthDate = $e->birth_date?->toDateString() ?? '';
        $this->bioMaritalStatus = (string) ($e->marital_status ?: 'single');
        $this->bioReligion = (string) ($e->religion ?: 'islam');
        $this->bioTaxDependents = (int) $e->tax_dependents;
        $this->bioEducationLevel = (string) ($e->education_level ?? 'S1');
        $this->bioScheduleType = (string) ($e->schedule_type ?? 'non_shift');
        // Sensitive PII only pre-filled for users allowed to see it
        $this->bioNpwp = $canSensitive ? $e->npwp_number : null;
        $this->bioKtp = $canSensitive ? $e->ktp_number : null;

        $this->showBioModal = true;
    }

    public function saveBio(AuditService $auditService): void
    {
        $this->authorize('update', $this->employee);
        $this->resetMessages();

        $canEditSensitive = (bool) Auth::user()?->can('viewSensitive', $this->employee);

        $rules = [
            'bioFullName' => ['required', 'string', 'max:150'],
            'bioEmail' => ['nullable', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($this->employee->id)],
            'bioPhone' => ['required', 'string', 'max:30'],
            'bioGender' => ['required', 'in:male,female'],
            'bioEducationLevel' => ['nullable', 'string', 'max:50'],
            'bioBirthPlace' => ['required', 'string', 'max:100'],
            'bioBirthDate' => ['required', 'date', 'before:today'],
            'bioMaritalStatus' => ['required', 'in:single,married,divorced,widowed'],
            'bioReligion' => ['nullable', 'in:islam,kristen,katolik,hindu,buddha,khonghucu'],
            'bioTaxDependents' => ['required', 'integer', 'min:0', 'max:3'],
            'bioScheduleType' => ['required', 'in:non_shift,shift'],
        ];
        if ($canEditSensitive) {
            $rules['bioKtp'] = ['required', 'digits:16'];
            $rules['bioNpwp'] = ['nullable', 'string', 'max:50'];
            $rules['bioAddress'] = ['required', 'string', 'max:500'];
        }

        $this->validate($rules, [
            'bioFullName.required' => 'Nama lengkap wajib diisi.',
            'bioEmail.email' => 'Format alamat email tidak valid.',
            'bioEmail.unique' => 'Alamat email sudah terdaftar pada pegawai lain.',
            'bioPhone.required' => 'Nomor telepon wajib diisi.',
            'bioAddress.required' => 'Alamat wajib diisi.',
            'bioBirthDate.before' => 'Tanggal lahir harus sebelum hari ini.',
            'bioTaxDependents.max' => 'Tanggungan PTKP maksimal 3 orang.',
            'bioKtp.digits' => 'NIK KTP harus 16 digit angka.',
        ]);

        $tracked = ['full_name', 'email', 'phone_number', 'residential_address', 'gender', 'education_level', 'birth_place', 'birth_date', 'marital_status', 'religion', 'tax_dependents', 'npwp_number', 'schedule_type'];
        $old = collect($this->employee->only($tracked))
            ->map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v)
            ->all();

        $data = [
            'full_name' => $this->bioFullName,
            'email' => $this->bioEmail ?: null,
            'phone_number' => $this->bioPhone,
            'gender' => $this->bioGender,
            'education_level' => $this->bioEducationLevel ?: null,
            'birth_place' => $this->bioBirthPlace,
            'birth_date' => $this->bioBirthDate,
            'marital_status' => $this->bioMaritalStatus,
            'religion' => $this->bioReligion ?: null,
            'tax_dependents' => $this->bioTaxDependents,
            'schedule_type' => $this->bioScheduleType,
        ];

        $ktpChanged = false;
        if ($canEditSensitive) {
            $data['residential_address'] = $this->bioAddress;
            $data['npwp_number'] = $this->bioNpwp ?: null;
            if ($this->bioKtp !== $this->employee->ktp_number) {
                $data['ktp_number'] = $this->bioKtp;
                $ktpChanged = true;
            }
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

        DB::transaction(function () use ($auditService) {
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
        $empCode = $this->employee->nik ?: ($this->employee->employee_id ?: 'EMP'.$this->employee->id);
        $cleanName = trim(preg_replace('/[^\w\s\-\.]+/u', '', $this->employee->name));
        $empFolder = "{$empCode} - {$cleanName}";
        $docFolder = strtoupper($this->docUploadType);
        $category = "Pegawai/{$empFolder}/{$docFolder}";
        $customFileName = "{$docFolder}_{$originalName}";

        $stored = $documentService->upload(
            category: $category,
            file: $this->docUploadedFile,
            customFileName: $customFileName,
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
        $this->salaryFeedback = 'Gaji pokok pegawai berhasil diperbarui dengan tanggal efektif '.Carbon::parse($this->salaryEffectiveFrom)->format('d/m/Y').'.';
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
        if (! $embeddingResult->isSuccessful) {
            $this->enrollErrorMessage = 'Ekstraksi fitur biometrik gagal: '.$embeddingResult->errorMessage;

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

    public function approveFaceRegistration(int $registrationId, FaceAiService $faceAiService, AuditService $auditService): void
    {
        $this->authorize('manageBiometrics', $this->employee);
        $this->resetMessages();

        $registration = FaceBiometricRegistration::where('employee_id', $this->employee->id)->findOrFail($registrationId);

        if (! $registration->isPending()) {
            $this->enrollErrorMessage = 'Pengajuan pendaftaran biometrik ini sudah diproses.';

            return;
        }

        if (! Storage::disk('local')->exists($registration->photo_path)) {
            $this->enrollErrorMessage = 'Berkas foto registrasi tidak ditemukan di server.';

            return;
        }

        $imageContent = Storage::disk('local')->get($registration->photo_path);

        // Extract facial embedding
        $embeddingResult = $faceAiService->extractEmbedding($imageContent);
        if (! $embeddingResult->isSuccessful) {
            $this->enrollErrorMessage = 'Ekstraksi fitur biometrik gagal: '.$embeddingResult->errorMessage;

            return;
        }

        $hadPrevious = ! empty($this->employee->face_biometric_vector);

        // Save encrypted vector
        $this->employee->face_biometric_vector = $embeddingResult->embedding;
        $this->employee->save();

        // Mark registration as approved
        $registration->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $auditService->log(
            action: 'employee.face_registration_approved',
            auditable: $this->employee,
            oldValues: ['has_biometric' => $hadPrevious],
            newValues: [
                'has_biometric' => true,
                'registration_id' => $registration->id,
                'vector_dimensions' => count($embeddingResult->embedding),
            ]
        );

        $this->employee->refresh();
        $this->enrollStatusMessage = 'Pengajuan biometrik wajah berhasil disetujui. Vektor biometrik wajah pegawai telah aktif untuk presensi mandiri.';
    }

    public function openRejectRegistrationModal(int $registrationId): void
    {
        $this->authorize('manageBiometrics', $this->employee);
        $this->resetMessages();
        $this->selectedRegistrationId = $registrationId;
        $this->registrationRejectionReason = '';
        $this->showRejectRegistrationModal = true;
    }

    public function closeRejectRegistrationModal(): void
    {
        $this->selectedRegistrationId = null;
        $this->registrationRejectionReason = '';
        $this->showRejectRegistrationModal = false;
    }

    public function rejectFaceRegistration(AuditService $auditService): void
    {
        $this->authorize('manageBiometrics', $this->employee);
        $this->validate([
            'registrationRejectionReason' => 'required|string|min:3|max:500',
        ]);

        $registration = FaceBiometricRegistration::where('employee_id', $this->employee->id)->findOrFail($this->selectedRegistrationId);

        if (! $registration->isPending()) {
            $this->enrollErrorMessage = 'Pengajuan pendaftaran biometrik ini sudah diproses.';
            $this->closeRejectRegistrationModal();

            return;
        }

        $registration->update([
            'status' => 'rejected',
            'rejection_reason' => $this->registrationRejectionReason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $auditService->log(
            action: 'employee.face_registration_rejected',
            auditable: $this->employee,
            oldValues: ['status' => 'pending'],
            newValues: [
                'status' => 'rejected',
                'registration_id' => $registration->id,
                'rejection_reason' => $this->registrationRejectionReason,
            ]
        );

        $this->closeRejectRegistrationModal();
        $this->employee->refresh();
        $this->enrollStatusMessage = 'Pengajuan pendaftaran biometrik wajah telah ditolak.';
    }

    // =========================================================
    // SANKSI & SURAT PERINGATAN (DISCIPLINARY / SP)
    // =========================================================

    public function openCreateWarningLetter(): void
    {
        $this->authorize('manageDisciplinary', $this->employee);
        $this->resetMessages();

        $this->reset([
            'editingWarningLetterId',
            'spViolationReason',
            'spNotes',
            'spDocumentFile',
        ]);

        $defaultType = WarningLetterType::active()->orderBy('level')->first();
        $this->spTypeId = $defaultType?->id;
        $this->spIssuedDate = Carbon::today()->toDateString();
        $this->spValidFrom = Carbon::today()->toDateString();

        $validityMonths = $defaultType?->validity_months ?? 6;
        $this->spValidUntil = Carbon::parse($this->spValidFrom)->addMonths($validityMonths)->toDateString();

        $count = $this->employee->warningLetters()->count() + 1;
        $this->spLetterNumber = sprintf('%03d/SP/HRD-RSJ/%s/%s', $count, date('m'), date('Y'));
        $this->spAuthorizedBy = Auth::user()?->name ?? 'HRD RS Juwita';

        $this->showWarningLetterModal = true;
    }

    public function updatedSpTypeId(?int $typeId): void
    {
        if ($typeId && $this->spValidFrom) {
            $type = WarningLetterType::find($typeId);
            $months = $type?->validity_months ?? 6;
            $this->spValidUntil = Carbon::parse($this->spValidFrom)->addMonths($months)->toDateString();
        }
    }

    public function updatedSpValidFrom(string $date): void
    {
        if ($date && $this->spTypeId) {
            $type = WarningLetterType::find($this->spTypeId);
            $months = $type?->validity_months ?? 6;
            $this->spValidUntil = Carbon::parse($date)->addMonths($months)->toDateString();
        }
    }

    public function editWarningLetter(int $id): void
    {
        $this->authorize('manageDisciplinary', $this->employee);
        $this->resetMessages();

        $sp = EmployeeWarningLetter::where('employee_id', $this->employee->id)->findOrFail($id);
        $this->editingWarningLetterId = $sp->id;
        $this->spTypeId = $sp->warning_letter_type_id;
        $this->spLetterNumber = $sp->letter_number;
        $this->spIssuedDate = $sp->issued_date ? $sp->issued_date->toDateString() : '';
        $this->spValidFrom = $sp->valid_from ? $sp->valid_from->toDateString() : '';
        $this->spValidUntil = $sp->valid_until ? $sp->valid_until->toDateString() : '';
        $this->spViolationReason = $sp->violation_reason;
        $this->spAuthorizedBy = $sp->authorized_by;
        $this->spNotes = $sp->notes ?? '';
        $this->spDocumentFile = null;

        $this->showWarningLetterModal = true;
    }

    public function saveWarningLetter(AuditService $auditService): void
    {
        $this->authorize('manageDisciplinary', $this->employee);
        $this->resetMessages();

        $this->validate([
            'spTypeId' => 'required|exists:warning_letter_types,id',
            'spLetterNumber' => 'required|string|max:100',
            'spIssuedDate' => 'required|date',
            'spValidFrom' => 'required|date',
            'spValidUntil' => 'required|date|after_or_equal:spValidFrom',
            'spViolationReason' => 'required|string|max:2000',
            'spAuthorizedBy' => 'required|string|max:150',
            'spNotes' => 'nullable|string|max:1000',
            'spDocumentFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($this->spDocumentFile) {
            $fileName = 'SP_'.$this->employee->nip.'_'.time().'.'.$this->spDocumentFile->getClientOriginalExtension();
            $filePath = $this->spDocumentFile->storeAs('warning_letters', $fileName, 'public');
        }

        if ($this->editingWarningLetterId) {
            $sp = EmployeeWarningLetter::where('employee_id', $this->employee->id)->findOrFail($this->editingWarningLetterId);
            $oldValues = $sp->toArray();

            $data = [
                'warning_letter_type_id' => $this->spTypeId,
                'letter_number' => trim($this->spLetterNumber),
                'issued_date' => $this->spIssuedDate,
                'valid_from' => $this->spValidFrom,
                'valid_until' => $this->spValidUntil,
                'violation_reason' => trim($this->spViolationReason),
                'authorized_by' => trim($this->spAuthorizedBy),
                'notes' => trim($this->spNotes) ?: null,
            ];

            if ($filePath) {
                $data['document_path'] = $filePath;
            }

            $sp->update($data);

            $auditService->log(
                action: 'employee.warning_letter_updated',
                auditable: $sp,
                oldValues: $oldValues,
                newValues: $sp->toArray()
            );

            $this->spFeedback = "Surat Peringatan {$sp->letter_number} berhasil diperbarui.";
        } else {
            $sp = EmployeeWarningLetter::create([
                'employee_id' => $this->employee->id,
                'warning_letter_type_id' => $this->spTypeId,
                'letter_number' => trim($this->spLetterNumber),
                'issued_date' => $this->spIssuedDate,
                'valid_from' => $this->spValidFrom,
                'valid_until' => $this->spValidUntil,
                'violation_reason' => trim($this->spViolationReason),
                'authorized_by' => trim($this->spAuthorizedBy),
                'status' => 'active',
                'document_path' => $filePath,
                'notes' => trim($this->spNotes) ?: null,
                'created_by' => Auth::id(),
            ]);

            $auditService->log(
                action: 'employee.warning_letter_issued',
                auditable: $sp,
                newValues: [
                    'employee_id' => $this->employee->id,
                    'letter_number' => $sp->letter_number,
                    'type_id' => $sp->warning_letter_type_id,
                    'valid_until' => $sp->valid_until->toDateString(),
                ]
            );

            $this->spFeedback = "Surat Peringatan {$sp->letter_number} berhasil diterbitkan untuk pegawai {$this->employee->full_name}.";
        }

        $this->showWarningLetterModal = false;
        $this->employee->refresh();
    }

    public function openRevokeModal(int $id): void
    {
        $this->authorize('manageDisciplinary', $this->employee);
        $this->resetMessages();

        $this->revokingWarningLetterId = $id;
        $this->revocationReason = '';
        $this->showRevokeModal = true;
    }

    public function revokeWarningLetter(AuditService $auditService): void
    {
        $this->authorize('manageDisciplinary', $this->employee);
        $this->validate([
            'revocationReason' => 'required|string|min:5|max:1000',
        ]);

        $sp = EmployeeWarningLetter::where('employee_id', $this->employee->id)->findOrFail($this->revokingWarningLetterId);
        $oldStatus = $sp->status;

        $sp->update([
            'status' => 'revoked',
            'revocation_reason' => trim($this->revocationReason),
            'revoked_at' => now(),
            'revoked_by' => Auth::id(),
        ]);

        $auditService->log(
            action: 'employee.warning_letter_revoked',
            auditable: $sp,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'revoked', 'reason' => $this->revocationReason]
        );

        $this->showRevokeModal = false;
        $this->spFeedback = "Surat Peringatan {$sp->letter_number} berhasil dicabut.";
        $this->employee->refresh();
    }

    public function downloadWarningLetter(int $id)
    {
        $this->authorize('viewDisciplinary', $this->employee);

        $sp = EmployeeWarningLetter::where('employee_id', $this->employee->id)->findOrFail($id);

        if (! $sp->document_path || ! Storage::disk('public')->exists($sp->document_path)) {
            $this->spError = 'Berkas fisik surat peringatan tidak ditemukan di penyimpanan server.';

            return null;
        }

        return Storage::disk('public')->download($sp->document_path, basename($sp->document_path));
    }

    public function deleteWarningLetter(int $id, AuditService $auditService): void
    {
        $this->authorize('manageDisciplinary', $this->employee);
        $this->resetMessages();

        $sp = EmployeeWarningLetter::where('employee_id', $this->employee->id)->findOrFail($id);
        $letterNumber = $sp->letter_number;
        $sp->delete();

        $auditService->log(
            action: 'employee.warning_letter_deleted',
            auditable: $sp,
            oldValues: ['letter_number' => $letterNumber]
        );

        $this->spFeedback = "Surat Peringatan {$letterNumber} berhasil dihapus dari arsip.";
        $this->employee->refresh();
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
            'canUpdateEmployment' => $user?->can('updateEmployment', $this->employee),
            'canManageBank' => $user?->can('manageBank', $this->employee),
            'canUploadDoc' => $user?->can('uploadDocument', $this->employee),
            'canManageDisciplinary' => $user?->can('manageDisciplinary', $this->employee),
            'canViewDisciplinary' => $user?->can('viewDisciplinary', $this->employee),
            'availableUnits' => Unit::orderBy('name')->get(),
            'availablePositions' => Position::orderBy('title')->get(),
            'availableComponents' => SalaryComponent::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'assignedComponents' => $this->employee->salaryComponents()->with('component')->latest('effective_from')->get(),
            'availableWarningLetterTypes' => WarningLetterType::active()->orderBy('level')->get(),
            'warningLetters' => $this->employee->warningLetters()->with(['warningLetterType', 'revoker'])->get(),
            'hasActiveSp' => $this->employee->hasActiveWarningLetter(),
            'faceRegistrations' => $this->employee->faceBiometricRegistrations()->with(['requestedBy', 'reviewedBy'])->get(),
            'pendingFaceRegistrationsCount' => $this->employee->faceBiometricRegistrations()->pending()->count(),
        ]);
    }
}
