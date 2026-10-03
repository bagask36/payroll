<?php

namespace App\DTOs\Document;

readonly class StoredDocumentResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $fileIdentifier,
        public string $fileName,
        public string $mimeType,
        public int $sizeBytes,
        public string $provider,
        public array $metadata = []
    ) {}
}
