<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Document;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\DocumentSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PayrollSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DocumentTest extends TestCase
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
            ScheduleSeeder::class,
            AttendanceSeeder::class,
            LeaveSeeder::class,
            PayrollSeeder::class,
            DocumentSeeder::class,
        ]);
    }

    public function test_it_seeds_sample_compliance_documents(): void
    {
        $this->assertDatabaseHas('documents', [
            'document_type' => 'ktp',
            'original_name' => 'KTP_Budi_Santoso.pdf',
        ]);

        $this->assertDatabaseHas('documents', [
            'document_type' => 'sip',
            'original_name' => 'SIP_dr_Hendra_Pratama.pdf',
        ]);
    }

    public function test_polymorphic_relationships(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $this->assertNotEmpty($nurse->documents);
        $this->assertEquals('ktp', $nurse->documents->first()->document_type);

        $doctor = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->firstOrFail();
        $this->assertNotEmpty($doctor->documents);
        $this->assertEquals('sip', $doctor->documents->first()->document_type);

        $doc = $doctor->documents->first();
        $this->assertInstanceOf(Doctor::class, $doc->documentable);
        $this->assertEquals($doctor->id, $doc->documentable->id);
    }

    public function test_temporary_download_url_generation(): void
    {
        $document = Document::firstOrFail();
        $url = $document->getTemporaryDownloadUrl(15);

        $this->assertNotEmpty($url);
        $this->assertIsString($url);
    }

    public function test_document_policy_authorization(): void
    {
        $nurse = Employee::where('nip', 'PEG-2025042')->firstOrFail();
        $nurseDoc = $nurse->documents->firstOrFail();
        $nurseUser = $nurse->user;

        // 1. Employee viewing and downloading their OWN document: ALLOWED
        $this->assertTrue(Gate::forUser($nurseUser)->allows('view', $nurseDoc));
        $this->assertTrue(Gate::forUser($nurseUser)->allows('download', $nurseDoc));

        // 2. Another employee viewing someone else's document: DENIED
        $otherEmployee = User::factory()->create();
        $otherEmployee->assignRole('employee');
        $this->assertFalse(Gate::forUser($otherEmployee)->allows('view', $nurseDoc));
        $this->assertFalse(Gate::forUser($otherEmployee)->allows('download', $nurseDoc));

        // 3. HR Admin with document.view_all and document.download: ALLOWED
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('hr_admin');
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('view', $nurseDoc));
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('download', $nurseDoc));

        // 4. Deleting a document requires document.delete (HR Manager allowed, HR Admin denied)
        $this->assertFalse(Gate::forUser($hrAdmin)->allows('delete', $nurseDoc));

        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');
        $this->assertTrue(Gate::forUser($hrManager)->allows('delete', $nurseDoc));
    }
}
