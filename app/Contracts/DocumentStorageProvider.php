<?php

namespace App\Contracts;

use App\DTOs\Document\StoredDocumentResult;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;

interface DocumentStorageProvider
{
    /**
     * Store file content under a specified directory/namespace.
     *
     * @param  string  $directory  e.g. 'employees/ktp', 'doctors/str'
     * @param  UploadedFile|string|resource  $file
     * @param  array<string, mixed>  $options
     */
    public function store(
        string $directory,
        UploadedFile|string $file,
        ?string $customFileName = null,
        array $options = []
    ): StoredDocumentResult;

    /**
     * Retrieve file content stream or binary string.
     */
    public function get(string $fileIdentifier): ?string;

    /**
     * Generate a temporary / presigned download URL for secure access.
     */
    public function getTemporaryUrl(string $fileIdentifier, DateTimeInterface $expiration): string;

    /**
     * Delete file by its identifier.
     */
    public function delete(string $fileIdentifier): bool;

    /**
     * Check if file exists.
     */
    public function exists(string $fileIdentifier): bool;

    /**
     * Provider name identifier (e.g. 'google_drive', 'local', 's3').
     */
    public function name(): string;
}
