<?php

namespace Tests\Feature;

use App\Livewire\Document\DocumentIndex;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\DocumentSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            OrganizationSeeder::class,
            EmployeeSeeder::class,
            DoctorSeeder::class,
            DocumentSeeder::class,
        ]);
    }

    public function test_hr_admin_can_view_all_documents_and_filter(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(DocumentIndex::class)
            ->assertStatus(200)
            ->assertSee('Arsip Dokumen Kepatuhan')
            ->assertSee('Penyimpanan terenkripsi dokumen resmi');
    }

    public function test_employee_can_only_view_their_own_documents(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseUser = $nurse->user;

        // Create a document belonging to another employee
        $otherEmployee = Employee::where('nip', '!=', 'PEG-2025042')->firstOrFail();
        $otherDoc = Document::factory()->create([
            'documentable_type' => Employee::class,
            'documentable_id' => $otherEmployee->id,
            'original_name' => 'rahasia_pegawai_lain.pdf',
            'uploaded_by' => $otherEmployee->user_id ?? 1,
        ]);

        // Create a document belonging to the nurse
        $nurseDoc = Document::factory()->create([
            'documentable_type' => Employee::class,
            'documentable_id' => $nurse->id,
            'original_name' => 'ijazah_perawat_dewi.pdf',
            'uploaded_by' => $nurseUser->id,
        ]);

        $this->actingAs($nurseUser);

        $this->get('/documents')
            ->assertStatus(200)
            ->assertSee('ijazah_perawat_dewi.pdf')
            ->assertDontSee('rahasia_pegawai_lain.pdf');

        Livewire::test(\App\Livewire\Tables\DocumentTable::class)
            ->assertStatus(200)
            ->assertSee('ijazah_perawat_dewi.pdf')
            ->assertDontSee('rahasia_pegawai_lain.pdf');
    }

    public function test_employee_can_upload_compliance_document(): void
    {
        Storage::fake('local');

        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseUser = $nurse->user;

        $this->actingAs($nurseUser);

        $fakeFile = UploadedFile::fake()->create('sertifikat_btcls.pdf', 500, 'application/pdf');

        Livewire::test(DocumentIndex::class)
            ->set('uploadType', 'certificate')
            ->set('uploadedFile', $fakeFile)
            ->call('uploadDocument')
            ->assertHasNoErrors()
            ->assertSee('berhasil diunggah dan diarsipkan');

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Employee::class,
            'documentable_id' => $nurse->id,
            'document_type' => 'certificate',
            'original_name' => 'sertifikat_btcls.pdf',
        ]);
    }

    public function test_authorized_user_can_generate_temporary_download_url(): void
    {
        Storage::fake('local');

        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseUser = $nurse->user;

        $doc = Document::factory()->create([
            'documentable_type' => Employee::class,
            'documentable_id' => $nurse->id,
            'uploaded_by' => $nurseUser->id,
            'original_name' => 'ktp_nurse.jpg',
            'storage_path' => 'documents/ktp_nurse.jpg',
        ]);

        $this->actingAs($nurseUser);

        Livewire::test(DocumentIndex::class)
            ->call('generateDownloadUrl', $doc->id)
            ->assertHasNoErrors()
            ->assertSee('Tautan unduh sementara');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.downloaded',
            'auditable_id' => $doc->id,
        ]);
    }
}
