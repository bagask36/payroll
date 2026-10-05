<?php

namespace Tests\Feature;

use App\Contracts\DocumentStorageProvider;
use App\Jobs\UploadAttendancePhotoToGoogleDrive;
use App\Models\Attendance;
use App\Models\AttendanceEvent;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\Document\DocumentService;
use App\Services\Document\GoogleDriveStorageProvider;
use App\Services\Document\LocalStorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentDirectoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_photo_job_builds_date_and_employee_hierarchy(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'nip' => '102030',
            'full_name' => 'Dr. Bagas Kurniawan',
        ]);

        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => Carbon::parse('2026-10-05'),
        ]);

        $testPhotoPath = 'attendance-photos/temp_clock_in.jpg';
        Storage::disk('public')->put($testPhotoPath, 'fake-photo-content');

        $event = AttendanceEvent::create([
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_in',
            'recorded_at' => Carbon::parse('2026-10-05 08:15:30'),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_meters' => 5,
            'is_within_geofence' => true,
            'photo_path' => $testPhotoPath,
            'storage_provider' => 'local',
        ]);

        $job = new UploadAttendancePhotoToGoogleDrive($event->id, $testPhotoPath);
        $provider = app(GoogleDriveStorageProvider::class);

        $job->handle($provider);

        $event->refresh();

        $this->assertSame('google_drive', $event->storage_provider);
        $this->assertNotEmpty($event->photo_path);
        // Local temporary file must be purged
        $this->assertFalse(Storage::disk('public')->exists($testPhotoPath));
    }

    public function test_document_service_resolves_clean_directory_for_employee_and_sk(): void
    {
        Storage::fake('local');

        $service = new DocumentService(new LocalStorageProvider('local'));
        $file = UploadedFile::fake()->create('ktp_pegawai.pdf', 100, 'application/pdf');

        // Test employee KTP hierarchy
        $employeeFolder = 'Pegawai/102030 - Bagas Kurniawan/KTP';
        $result = $service->upload($employeeFolder, $file, 'KTP_102030_Bagas_Kurniawan.pdf');

        $this->assertSame('local', $result->provider);
        $this->assertSame('KTP_102030_Bagas_Kurniawan.pdf', $result->fileName);
        Storage::disk('local')->assertExists($result->fileIdentifier);

        // Test SK Jabatan hierarchy
        $skFile = UploadedFile::fake()->create('sk_jabatan.pdf', 150, 'application/pdf');
        $skFolder = 'SK_Jabatan/Perawat Pelaksana ICU';
        $skResult = $service->upload($skFolder, $skFile, 'SK_Jabatan_Perawat_Pelaksana_ICU.pdf');

        $this->assertSame('SK_Jabatan_Perawat_Pelaksana_ICU.pdf', $skResult->fileName);
        Storage::disk('local')->assertExists($skResult->fileIdentifier);
    }
}
