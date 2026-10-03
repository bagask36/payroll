<?php

namespace App\Services\Document;

use App\Contracts\DocumentStorageProvider;
use App\DTOs\Document\StoredDocumentResult;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocalStorageProvider implements DocumentStorageProvider
{
    /**
     * @param  string  $disk  Private local storage disk name
     */
    public function __construct(
        protected string $disk = 'local'
    ) {}

    public function store(
        string $directory,
        UploadedFile|string $file,
        ?string $customFileName = null,
        array $options = []
    ): StoredDocumentResult {
        if ($file instanceof UploadedFile) {
            $fileName = $customFileName ?? (Str::uuid()->toString().'.'.$file->getClientOriginalExtension());
            $filePath = trim($directory, '/').'/'.$fileName;

            Storage::disk($this->disk)->putFileAs(trim($directory, '/'), $file, $fileName);

            return new StoredDocumentResult(
                fileIdentifier: $filePath,
                fileName: $file->getClientOriginalName(),
                mimeType: $file->getClientMimeType() ?: 'application/octet-stream',
                sizeBytes: (int) $file->getSize(),
                provider: $this->name(),
                metadata: ['disk' => $this->disk]
            );
        }

        // Storing from raw string / content
        $fileName = $customFileName ?? (Str::uuid()->toString().'.bin');
        $filePath = trim($directory, '/').'/'.$fileName;

        Storage::disk($this->disk)->put($filePath, $file);

        return new StoredDocumentResult(
            fileIdentifier: $filePath,
            fileName: $fileName,
            mimeType: 'application/octet-stream',
            sizeBytes: strlen($file),
            provider: $this->name(),
            metadata: ['disk' => $this->disk]
        );
    }

    public function get(string $fileIdentifier): ?string
    {
        if (! Storage::disk($this->disk)->exists($fileIdentifier)) {
            return null;
        }

        return Storage::disk($this->disk)->get($fileIdentifier);
    }

    public function getTemporaryUrl(string $fileIdentifier, DateTimeInterface $expiration): string
    {
        // For local private storage, return a routed download signature URL
        return Storage::disk($this->disk)->temporaryUrl($fileIdentifier, $expiration);
    }

    public function delete(string $fileIdentifier): bool
    {
        return Storage::disk($this->disk)->delete($fileIdentifier);
    }

    public function exists(string $fileIdentifier): bool
    {
        return Storage::disk($this->disk)->exists($fileIdentifier);
    }

    public function name(): string
    {
        return 'local';
    }
}
