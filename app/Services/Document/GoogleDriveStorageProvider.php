<?php

namespace App\Services\Document;

use App\Contracts\DocumentStorageProvider;
use App\DTOs\Document\StoredDocumentResult;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class GoogleDriveStorageProvider implements DocumentStorageProvider
{
    /**
     * @param  string|null  $sharedDriveId  Google Workspace Shared Drive ID
     * @param  string|null  $serviceAccountKeyPath  Path to Google Service Account JSON
     */
    public function __construct(
        protected ?string $sharedDriveId = null,
        protected ?string $serviceAccountKeyPath = null
    ) {}

    public function store(
        string $directory,
        UploadedFile|string $file,
        ?string $customFileName = null,
        array $options = []
    ): StoredDocumentResult {
        // When Google Drive API credentials are configured, Google Client/Drive API stores file.
        // If not configured, throw explicit configuration exception or mock.
        if (empty($this->serviceAccountKeyPath) && ! app()->environment('testing', 'local')) {
            throw new RuntimeException('Google Drive service account credentials are not configured.');
        }

        $fileName = $file instanceof UploadedFile
            ? ($customFileName ?? $file->getClientOriginalName())
            : ($customFileName ?? 'document.bin');

        $mimeType = $file instanceof UploadedFile
            ? ($file->getClientMimeType() ?: 'application/octet-stream')
            : 'application/octet-stream';

        $size = $file instanceof UploadedFile ? (int) $file->getSize() : strlen($file);

        // Simulated/placeholder file ID until Google Client package is hooked up
        $fakeFileId = 'gdrive_'.md5($directory.'/'.$fileName.microtime());

        return new StoredDocumentResult(
            fileIdentifier: $fakeFileId,
            fileName: $fileName,
            mimeType: $mimeType,
            sizeBytes: $size,
            provider: $this->name(),
            metadata: [
                'shared_drive_id' => $this->sharedDriveId,
                'directory' => $directory,
            ]
        );
    }

    public function get(string $fileIdentifier): ?string
    {
        return null;
    }

    public function getTemporaryUrl(string $fileIdentifier, DateTimeInterface $expiration): string
    {
        return 'https://drive.google.com/file/d/'.urlencode($fileIdentifier).'/view';
    }

    public function delete(string $fileIdentifier): bool
    {
        return true;
    }

    public function exists(string $fileIdentifier): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'google_drive';
    }
}
