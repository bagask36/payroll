<?php

namespace App\Services\FaceAi;

use App\Contracts\FaceAiService;
use App\DTOs\FaceAi\FaceEmbeddingResult;
use App\DTOs\FaceAi\FaceVerificationResult;
use App\DTOs\FaceAi\LivenessResult;

class LocalMockFaceAiService implements FaceAiService
{
    /**
     * @param  float  $defaultThreshold  Default minimum similarity score
     */
    public function __construct(
        protected float $defaultThreshold = 0.85
    ) {}

    public function checkLiveness(string $imageContent, array $options = []): LivenessResult
    {
        if (empty($imageContent)) {
            return LivenessResult::failed('Image content is empty.');
        }

        // In test/mock mode, unless requested to simulate spoof, treat as live.
        $forceSpoof = $options['simulate_spoof'] ?? false;
        if ($forceSpoof) {
            return LivenessResult::spoof(0.20, 'Mock detected static picture attack.');
        }

        return LivenessResult::live(0.98, ['engine' => 'local_mock']);
    }

    public function extractEmbedding(string $imageContent, array $options = []): FaceEmbeddingResult
    {
        if (empty($imageContent)) {
            return FaceEmbeddingResult::failed('Image content is empty.');
        }

        // Return a mock 128-dimensional embedding vector
        $embedding = array_fill(0, 128, 0.05);

        return FaceEmbeddingResult::success($embedding, ['engine' => 'local_mock']);
    }

    public function verify(string $capturedImageContent, array|string $referenceEmbeddingOrTemplate, array $options = []): FaceVerificationResult
    {
        if (empty($capturedImageContent)) {
            return FaceVerificationResult::failed('Captured image content is empty.');
        }

        $threshold = (float) ($options['threshold'] ?? $this->defaultThreshold);
        $simulateMismatch = $options['simulate_mismatch'] ?? false;

        if ($simulateMismatch) {
            return FaceVerificationResult::mismatched(0.45, $threshold);
        }

        return FaceVerificationResult::matched(0.94, $threshold, ['engine' => 'local_mock']);
    }
}
