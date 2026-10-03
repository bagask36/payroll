<?php

namespace App\DTOs\FaceAi;

readonly class LivenessResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $isLive,
        public float $confidenceScore,
        public ?string $errorMessage = null,
        public array $metadata = []
    ) {}

    public static function live(float $score, array $metadata = []): self
    {
        return new self(
            isLive: true,
            confidenceScore: $score,
            metadata: $metadata
        );
    }

    public static function spoof(float $score, ?string $reason = null): self
    {
        return new self(
            isLive: false,
            confidenceScore: $score,
            errorMessage: $reason ?? 'Liveness check failed: possible spoof or photo detected.'
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            isLive: false,
            confidenceScore: 0.0,
            errorMessage: $errorMessage
        );
    }
}
