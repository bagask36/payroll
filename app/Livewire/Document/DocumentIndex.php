<?php

namespace App\Livewire\Document;

use App\Livewire\Concerns\WithDataTable;
use App\Models\Doctor;
use App\Models\Document;
use App\Models\Employee;
use App\Services\Audit\AuditService;
use App\Services\Document\DocumentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Arsip Dokumen Kepatuhan - RS Permata Bunda')]
class DocumentIndex extends Component
{
    use WithDataTable, WithFileUploads;

    #[Url(except: 'all')]
    public string $documentTypeFilter = 'all';

    protected function sortableColumns(): array
    {
        return [
            'created_at' => 'created_at',
            'original_name' => 'original_name',
            'document_type' => 'document_type',
            'file_size' => 'file_size',
        ];
    }

    protected function filterProperties(): array
    {
        return [
            'documentTypeFilter' => 'all',
        ];
    }

    // Upload Modal State
    public bool $showUploadModal = false;

    #[Rule(['required', 'string', 'in:ktp,kk,ijazah,contract,str,sip,certificate,other'])]
    public string $uploadType = 'certificate';

    #[Rule(['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'])]
    public $uploadedFile = null;

    public ?int $targetEmployeeId = null;

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user->hasPermission('document.view_own') && ! $user->hasPermission('document.view_all')) {
            abort(403, 'Akses ke arsip dokumen ditolak.');
        }

        if ($user->employee) {
            $this->targetEmployeeId = $user->employee->id;
        }
    }

    public function openUploadModal(): void
    {
        $this->authorize('create', Document::class);
        $this->showUploadModal = true;
    }

    public function uploadDocument(DocumentService $documentService, AuditService $auditService): void
    {
        $this->authorize('create', Document::class);
        $this->validate();

        $user = Auth::user();

        // Determine target documentable
        if ($user->hasPermission('document.upload_all') && $this->targetEmployeeId) {
            $owner = Employee::findOrFail($this->targetEmployeeId);
        } else {
            $owner = $user->employee ?? $user->doctor;
            if (! $owner) {
                $this->errorMessage = 'Profil pegawai atau dokter Anda tidak ditemukan.';

                return;
            }
        }

        $category = 'compliance/'.$this->uploadType;
        $stored = $documentService->upload(
            category: $category,
            file: $this->uploadedFile,
            customFileName: $this->uploadedFile->getClientOriginalName(),
        );

        $document = Document::create([
            'documentable_type' => get_class($owner),
            'documentable_id' => $owner->id,
            'document_type' => $this->uploadType,
            'original_name' => $this->uploadedFile->getClientOriginalName(),
            'storage_path' => $stored->fileIdentifier,
            'mime_type' => $stored->mimeType,
            'file_size' => $stored->sizeBytes,
            'storage_provider' => $stored->provider,
            'uploaded_by' => $user->id,
        ]);

        $auditService->log(
            action: 'document.uploaded',
            auditable: $document,
            newValues: [
                'document_type' => $this->uploadType,
                'original_name' => $document->original_name,
                'file_size' => $document->file_size,
            ]
        );

        $this->reset(['uploadedFile', 'showUploadModal']);
        $this->feedbackMessage = 'Dokumen berhasil diunggah dan diarsipkan dengan aman!';
        $this->dispatch('pg:eventRefresh-documents_table');
    }

    #[On('download-document')]
    public function generateDownloadUrl(int $documentId, AuditService $auditService): ?string
    {
        $doc = Document::findOrFail($documentId);
        $this->authorize('download', $doc);

        $downloadUrl = $doc->getTemporaryDownloadUrl(15);

        $auditService->log(
            action: 'document.downloaded',
            auditable: $doc,
            newValues: ['document_type' => $doc->document_type]
        );

        $this->feedbackMessage = 'Tautan unduh sementara (berlaku 15 menit) berhasil dibuat!';

        return $downloadUrl;
    }

    public function render()
    {
        $user = Auth::user();
        $query = Document::with(['documentable', 'uploadedBy'])->orderByDesc('created_at');

        // Segregate access: if not document.view_all, restrict to own documents
        if (! $user->hasPermission('document.view_all')) {
            $query->where(function ($q) use ($user) {
                $q->where('uploaded_by', $user->id);

                if ($user->employee) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('documentable_type', Employee::class)
                            ->where('documentable_id', $user->employee->id);
                    });
                }

                if ($user->doctor) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('documentable_type', Doctor::class)
                            ->where('documentable_id', $user->doctor->id);
                    });
                }
            });
        }

        if ($this->documentTypeFilter !== 'all') {
            $query->where('document_type', $this->documentTypeFilter);
        }

        if ($this->search !== '') {
            $term = '%'.strtolower($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(original_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(document_type) LIKE ?', [$term]);
            });
        }

        $this->applySorting($query, 'created_at', 'desc');

        $documents = $query->paginate($this->perPageValue());
        $employees = $user->hasPermission('document.upload_all')
            ? Employee::active()->orderBy('full_name')->get()
            : collect();

        return view('livewire.document.document-index', [
            'documents' => $documents,
            'employees' => $employees,
        ]);
    }
}
