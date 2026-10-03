<?php

namespace Tests\Feature;

use App\Contracts\DocumentStorageProvider;
use App\Services\Document\DocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    public function test_it_resolves_document_service_and_storage_provider(): void
    {
        $service = app(DocumentService::class);
        $provider = app(DocumentStorageProvider::class);

        $this->assertInstanceOf(DocumentService::class, $service);
        $this->assertInstanceOf(DocumentStorageProvider::class, $provider);
    }

    public function test_it_uploads_and_retrieves_document(): void
    {
        Storage::fake('local');

        $service = app(DocumentService::class);
        $file = UploadedFile::fake()->create('ktp_employee.pdf', 256, 'application/pdf');

        $result = $service->upload('employees/ktp', $file);

        $this->assertNotEmpty($result->fileIdentifier);
        $this->assertSame('ktp_employee.pdf', $result->fileName);
        $this->assertSame('local', $result->provider);

        // Verify storage file exists
        Storage::disk('local')->assertExists($result->fileIdentifier);

        // Delete via document service
        $deleted = $service->delete($result->fileIdentifier);
        $this->assertTrue($deleted);
        Storage::disk('local')->assertMissing($result->fileIdentifier);
    }
}
