<?php

namespace App\Livewire\Employee;

use App\Contracts\FaceAiService;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Services\Audit\AuditService;
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
            'availableComponents' => SalaryComponent::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'assignedComponents' => $this->employee->salaryComponents()->with('component')->latest('effective_from')->get(),
        ]);
    }
}
