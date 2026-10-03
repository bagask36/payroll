<?php

namespace App\DTOs\FaceAi;

readonly class FaceVerificationResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $isMatched,
        public float $similarityScore,
        public float $confidenceThreshold,
        public bool $isLive = true,
        public ?string $errorMessage = null,
        public array $metadata = []
    ) {}

    public static function matched(float $similarityScore, float $confidenceThreshold, array $metadata = []): self
    {
        return new self(
            isMatched: true,
            similarityScore: $similarityScore,
            confidenceThreshold: $confidenceThreshold,
            isLive: true,
            metadata: $metadata
        );
    }

    public static function mismatched(float $similarityScore, float $confidenceThreshold, ?string $errorMessage = null): self
    {
        return new self(
            isMatched: false,
            similarityScore: $similarityScore,
            confidenceThreshold: $confidenceThreshold,
            isLive: true,
            errorMessage: $errorMessage ?? 'Face similarity score below required threshold.'
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            isMatched: false,
            similarityScore: 0.0,
            confidenceThreshold: 0.0,
            isLive: false,
            errorMessage: $errorMessage
        );
    }
}
