<?php

namespace App\Services\Document;

use App\Contracts\DocumentStorageProvider;
use App\DTOs\Document\StoredDocumentResult;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    /**
     * @param  DocumentStorageProvider  $storageProvider  The resolved default storage provider
     */
    public function __construct(
        protected DocumentStorageProvider $storageProvider
    ) {}

    /**
     * Store an employee or doctor document safely.
     *
     * @param  string  $category  e.g. 'employees/ktp', 'doctors/str', 'payroll/slips'
     */
    public function upload(
        string $category,
        UploadedFile|string $file,
        ?string $customFileName = null,
        array $options = []
    ): StoredDocumentResult {
        return $this->storageProvider->store($category, $file, $customFileName, $options);
    }

    /**
     * Retrieve document file binary content.
     */
    public function download(string $fileIdentifier): ?string
    {
        return $this->storageProvider->get($fileIdentifier);
    }

    /**
     * Generate secure temporary URL for document preview.
     */
    public function getPreviewUrl(string $fileIdentifier, DateTimeInterface $expiration): string
    {
        return $this->storageProvider->getTemporaryUrl($fileIdentifier, $expiration);
    }

    /**
     * Delete document from storage.
     */
    public function delete(string $fileIdentifier): bool
    {
        return $this->storageProvider->delete($fileIdentifier);
    }

    /**
     * Get active storage provider instance.
     */
    public function getProvider(): DocumentStorageProvider
    {
        return $this->storageProvider;
    }
}
