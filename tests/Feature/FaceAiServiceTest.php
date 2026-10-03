<?php

namespace Tests\Feature;

use App\Contracts\FaceAiService;
use Tests\TestCase;

class FaceAiServiceTest extends TestCase
{
    public function test_it_resolves_face_ai_service_from_container(): void
    {
        $service = app(FaceAiService::class);
        $this->assertInstanceOf(FaceAiService::class, $service);
    }

    public function test_it_checks_liveness_successfully(): void
    {
        $service = app(FaceAiService::class);
        $result = $service->checkLiveness('fake-binary-face-image');

        $this->assertTrue($result->isLive);
        $this->assertGreaterThan(0.8, $result->confidenceScore);
    }

    public function test_it_detects_spoof_when_simulated(): void
    {
        $service = app(FaceAiService::class);
        $result = $service->checkLiveness('fake-binary-face-image', ['simulate_spoof' => true]);

        $this->assertFalse($result->isLive);
        $this->assertNotEmpty($result->errorMessage);
    }

    public function test_it_extracts_embedding_vector(): void
    {
        $service = app(FaceAiService::class);
        $result = $service->extractEmbedding('fake-binary-face-image');

        $this->assertTrue($result->isSuccessful);
        $this->assertIsArray($result->embedding);
        $this->assertNotEmpty($result->embedding);
    }

    public function test_it_verifies_face_match_with_threshold(): void
    {
        $service = app(FaceAiService::class);
        $result = $service->verify('fake-captured-image', 'reference-template-123');

        $this->assertTrue($result->isMatched);
        $this->assertGreaterThanOrEqual($result->confidenceThreshold, $result->similarityScore);
    }
}
