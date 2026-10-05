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

    public function test_it_rejects_mismatched_different_faces(): void
    {
        $service = app(FaceAiService::class);

        // Person A
        $imgA = imagecreatetruecolor(100, 100);
        imagefilledrectangle($imgA, 0, 0, 99, 99, imagecolorallocate($imgA, 220, 200, 180));
        imagefilledellipse($imgA, 50, 50, 60, 70, imagecolorallocate($imgA, 240, 210, 190));
        imagefilledellipse($imgA, 40, 45, 10, 8, imagecolorallocate($imgA, 50, 40, 30));
        imagefilledellipse($imgA, 60, 45, 10, 8, imagecolorallocate($imgA, 50, 40, 30));
        ob_start();
        imagejpeg($imgA);
        $rawA = ob_get_clean();

        // Person B (completely different tone and features)
        $imgB = imagecreatetruecolor(100, 100);
        imagefilledrectangle($imgB, 0, 0, 99, 99, imagecolorallocate($imgB, 50, 50, 60));
        imagefilledellipse($imgB, 50, 50, 80, 50, imagecolorallocate($imgB, 120, 90, 80));
        imagefilledellipse($imgB, 35, 40, 16, 14, imagecolorallocate($imgB, 10, 10, 10));
        imagefilledellipse($imgB, 65, 40, 16, 14, imagecolorallocate($imgB, 10, 10, 10));
        ob_start();
        imagejpeg($imgB);
        $rawB = ob_get_clean();

        $embeddingA = $service->extractEmbedding($rawA)->embedding;

        $verification = $service->verify($rawB, $embeddingA, ['threshold' => 0.80]);

        $this->assertFalse($verification->isMatched);
        $this->assertLessThan(0.80, $verification->similarityScore);
    }

    public function test_it_rejects_low_motion_liveness(): void
    {
        $service = app(FaceAiService::class);

        // Test with low motion score (e.g. 0.20 when user is static or photo attack)
        $result = $service->checkLiveness('fake-binary-face-image', ['motion_score' => 0.20]);

        $this->assertFalse($result->isLive);
        $this->assertLessThan(0.50, $result->confidenceScore);
        $this->assertStringContainsString('liveness rendah', $result->errorMessage);
    }
}
