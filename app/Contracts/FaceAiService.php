<?php

namespace App\Contracts;

use App\DTOs\FaceAi\FaceEmbeddingResult;
use App\DTOs\FaceAi\FaceVerificationResult;
use App\DTOs\FaceAi\LivenessResult;

interface FaceAiService
{
    /**
     * Check liveness from a captured image/stream.
     *
     * @param  string  $imageContent  Raw binary or base64 encoded image
     * @param  array<string, mixed>  $options
     */
    public function checkLiveness(string $imageContent, array $options = []): LivenessResult;

    /**
     * Extract facial feature embedding vector from an image.
     *
     * @param  string  $imageContent  Raw binary or base64 encoded image
     * @param  array<string, mixed>  $options
     */
    public function extractEmbedding(string $imageContent, array $options = []): FaceEmbeddingResult;

    /**
     * Verify a captured face image against a reference embedding vector or template.
     *
     * @param  list<float>|string  $referenceEmbeddingOrTemplate
     * @param  array<string, mixed>  $options
     */
    public function verify(string $capturedImageContent, array|string $referenceEmbeddingOrTemplate, array $options = []): FaceVerificationResult;
}
