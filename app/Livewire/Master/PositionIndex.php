<?php

namespace App\Livewire\Master;

use App\Models\Document;
use App\Models\Position;
use App\Models\Unit;
use App\Services\Audit\AuditService;
use App\Services\Document\DocumentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Data Master: Standar Jabatan - RS Juwita')]
class PositionIndex extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public bool $showPositionModal = false;

    public ?int $editingPositionId = null;

    public string $positionTitle = '';

    public ?int $positionUnitId = null;

    public ?int $positionReportsToId = null;

    public string $positionGradeLevel = 'III/a';

    public ?string $positionDefaultBasicSalary = '';

    public ?string $positionDefaultPositionalAllowance = '';

    public ?string $positionDefaultContractNumber = '';

    public $positionSkDocumentFile = null;

    public ?int $positionExistingDocumentId = null;

    public ?string $positionExistingDocumentName = null;

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasPermission('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke master data dibatasi khusus Administrator HR dan Super Admin.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreatePosition(): void
    {
        $this->reset([
            'editingPositionId',
            'positionTitle',
            'positionUnitId',
            'positionReportsToId',
            'positionDefaultBasicSalary',
            'positionDefaultPositionalAllowance',
            'positionDefaultContractNumber',
            'positionSkDocumentFile',
            'positionExistingDocumentId',
            'positionExistingDocumentName',
        ]);
        $this->positionGradeLevel = 'III/a';
        $this->showPositionModal = true;
    }

    #[On('editPosition')]
    public function editPosition(int $id): void
    {
        $pos = Position::with('document')->findOrFail($id);
        $this->editingPositionId = $pos->id;
        $this->positionTitle = $pos->title;
        $this->positionUnitId = $pos->unit_id;
        $this->positionReportsToId = $pos->reports_to_id;
        $this->positionGradeLevel = $pos->grade_level ?? 'III/a';
        $this->positionDefaultBasicSalary = $pos->default_basic_salary !== null ? (string) (float) $pos->default_basic_salary : '';
        $this->positionDefaultPositionalAllowance = $pos->default_positional_allowance !== null ? (string) (float) $pos->default_positional_allowance : '';
        $this->positionDefaultContractNumber = $pos->default_contract_number ?? '';
        $this->positionExistingDocumentId = $pos->document_id;
        $this->positionExistingDocumentName = $pos->document?->original_name;
        $this->positionSkDocumentFile = null;
        $this->showPositionModal = true;
    }

    public function savePosition(AuditService $auditService, DocumentService $documentService): void
    {
        $this->validate([
            'positionTitle' => 'required|string|max:100',
            'positionUnitId' => 'nullable|exists:units,id',
            'positionReportsToId' => 'nullable|exists:positions,id|different:editingPositionId',
            'positionGradeLevel' => 'nullable|string|max:20',
            'positionDefaultBasicSalary' => 'nullable|numeric|min:0',
            'positionDefaultPositionalAllowance' => 'nullable|numeric|min:0',
            'positionDefaultContractNumber' => 'nullable|string|max:100',
            'positionSkDocumentFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $uploadedDocId = $this->positionExistingDocumentId;

        if ($this->positionSkDocumentFile) {
            $originalName = $this->positionSkDocumentFile->getClientOriginalName();
            $posName = trim(preg_replace('/[^\w\s\-\.]+/u', '', $this->positionTitle ?: 'Jabatan'));
            $category = "SK_Jabatan/{$posName}";
            $customFileName = "SK_Jabatan_{$posName}_{$originalName}";

            $stored = $documentService->upload(
                category: $category,
                file: $this->positionSkDocumentFile,
                customFileName: $customFileName,
            );

            $doc = Document::create([
                'documentable_type' => Position::class,
                'documentable_id' => $this->editingPositionId ?? 0,
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

        $payload = [
            'title' => $this->positionTitle,
            'unit_id' => $this->positionUnitId,
            'reports_to_id' => $this->positionReportsToId,
            'grade_level' => $this->positionGradeLevel,
            'default_basic_salary' => $this->positionDefaultBasicSalary !== '' && $this->positionDefaultBasicSalary !== null ? $this->positionDefaultBasicSalary : null,
            'default_positional_allowance' => $this->positionDefaultPositionalAllowance !== '' && $this->positionDefaultPositionalAllowance !== null ? $this->positionDefaultPositionalAllowance : null,
            'default_contract_number' => $this->positionDefaultContractNumber ? trim($this->positionDefaultContractNumber) : null,
            'document_id' => $uploadedDocId,
        ];

        if ($this->editingPositionId) {
            $pos = Position::findOrFail($this->editingPositionId);
            $pos->update($payload);
            if ($this->positionSkDocumentFile && isset($doc)) {
                $doc->update(['documentable_id' => $pos->id]);
            }
            $action = 'position.updated';
        } else {
            $pos = Position::create($payload);
            if ($this->positionSkDocumentFile && isset($doc)) {
                $doc->update(['documentable_id' => $pos->id]);
            }
            $action = 'position.created';
        }

        $auditService->log(action: $action, auditable: $pos, newValues: ['title' => $pos->title]);
        $this->showPositionModal = false;
        $this->feedbackMessage = "Jabatan {$pos->title} berhasil disimpan!";
        $this->dispatch('pg:eventRefresh-positions_table');
    }

    public function downloadPositionDocument(int $positionId, DocumentService $documentService)
    {
        $pos = Position::with('document')->findOrFail($positionId);
        if (! $pos->document) {
            abort(404, 'Berkas fisik SK formasi tidak ditemukan.');
        }

        $content = $documentService->download($pos->document->storage_path);
        if ($content === null) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan server.');
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $pos->document->original_name, [
            'Content-Type' => $pos->document->mime_type,
        ]);
    }

    public function render()
    {
        $units = Unit::orderBy('name')->get();

        $allPositions = Position::when($this->editingPositionId, fn ($q) => $q->where('id', '!=', $this->editingPositionId))
            ->orderBy('title')
            ->get();

        $positions = Position::with(['unit', 'supervisorPosition'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderBy('title')
            ->paginate(15);

        return view('livewire.master.position-index', [
            'units' => $units,
            'allPositions' => $allPositions,
            'positions' => $positions,
        ]);
    }
}
