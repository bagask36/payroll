<?php

namespace App\DTOs\FaceAi;

readonly class FaceEmbeddingResult
{
    /**
     * @param  list<float>|null  $embedding
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $isSuccessful,
        public ?array $embedding = null,
        public ?string $errorMessage = null,
        public array $metadata = []
    ) {}

    /**
     * @param  list<float>  $embedding
     * @param  array<string, mixed>  $metadata
     */
    public static function success(array $embedding, array $metadata = []): self
    {
        return new self(
            isSuccessful: true,
            embedding: $embedding,
            metadata: $metadata
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            isSuccessful: false,
            errorMessage: $errorMessage
        );
    }
}
