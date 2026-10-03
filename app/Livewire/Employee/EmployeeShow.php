<?php

namespace App\Livewire\Employee;

use App\Contracts\FaceAiService;
use App\Models\Employee;
use App\Services\Audit\AuditService;
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

    public function mount(int $id): void
    {
        $this->employee = Employee::with([
            'unit',
            'position',
            'employments',
            'salaries',
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
            'canViewBank' => $user?->can('viewBank', $this->employee),
            'canManageBiometrics' => $user?->can('manageBiometrics', $this->employee),
        ]);
    }
}
