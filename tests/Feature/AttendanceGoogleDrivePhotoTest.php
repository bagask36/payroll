<?php

namespace Tests\Feature;

use App\Jobs\UploadAttendancePhotoToGoogleDrive;
use App\Models\Attendance;
use App\Models\AttendanceEvent;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Document\GoogleDriveStorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceGoogleDrivePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_url_attribute_generates_authorized_route(): void
    {
        $attendance = Attendance::factory()->create();
        $event = AttendanceEvent::create([
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_in',
            'recorded_at' => now(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_meters' => 10,
            'is_within_geofence' => true,
            'photo_path' => 'attendance-photos/test.jpg',
            'storage_provider' => 'local',
        ]);

        $this->assertSame(route('attendance.photo', $event->id), $event->photo_url);
    }

    public function test_job_uploads_photo_to_google_drive_and_purges_local_file(): void
    {
        Storage::fake('public');

        $testPhotoPath = 'attendance-photos/2026/10/sample_photo.jpg';
        Storage::disk('public')->put($testPhotoPath, 'fake-jpeg-binary-content');

        $this->assertTrue(Storage::disk('public')->exists($testPhotoPath));

        $attendance = Attendance::factory()->create();
        $event = AttendanceEvent::create([
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_in',
            'recorded_at' => now(),
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

        // Database updated with Google Drive provider and identifier
        $this->assertSame('google_drive', $event->storage_provider);
        $this->assertStringStartsWith('gdrive_', $event->photo_path);

        // Local storage file deleted (0 MB footprint)
        $this->assertFalse(Storage::disk('public')->exists($testPhotoPath));
    }

    public function test_unauthenticated_user_cannot_access_attendance_photo(): void
    {
        $attendance = Attendance::factory()->create();
        $event = AttendanceEvent::create([
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_in',
            'recorded_at' => now(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_meters' => 10,
            'is_within_geofence' => true,
            'photo_path' => 'attendance-photos/test.jpg',
            'storage_provider' => 'local',
        ]);

        $response = $this->get(route('attendance.photo', $event->id));
        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_is_forbidden_from_viewing_others_photo(): void
    {
        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id]);

        $attendance = Attendance::factory()->create(['employee_id' => $otherEmployee->id]);
        $event = AttendanceEvent::create([
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_in',
            'recorded_at' => now(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_meters' => 10,
            'is_within_geofence' => true,
            'photo_path' => 'attendance-photos/test.jpg',
            'storage_provider' => 'local',
        ]);

        $intruder = User::factory()->create();
        Employee::factory()->create(['user_id' => $intruder->id]);

        $response = $this->actingAs($intruder)->get(route('attendance.photo', $event->id));
        $response->assertStatus(403);
    }

    public function test_employee_can_view_own_attendance_photo(): void
    {
        Storage::fake('public');
        $photoPath = 'attendance-photos/own_photo.jpg';
        Storage::disk('public')->put($photoPath, 'binary-image-data');

        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);
        $event = AttendanceEvent::create([
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_in',
            'recorded_at' => now(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_meters' => 10,
            'is_within_geofence' => true,
            'photo_path' => $photoPath,
            'storage_provider' => 'local',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.photo', $event->id));
        $response->assertStatus(200);
    }

    public function test_super_admin_can_view_any_attendance_photo(): void
    {
        Storage::fake('public');
        $photoPath = 'attendance-photos/target_photo.jpg';
        Storage::disk('public')->put($photoPath, 'binary-image-data');

        $adminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Administrator']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);
        $employee = Employee::factory()->create();

        $attendance = Attendance::factory()->create(['employee_id' => $employee->id]);
        $event = AttendanceEvent::create([
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_in',
            'recorded_at' => now(),
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_meters' => 10,
            'is_within_geofence' => true,
            'photo_path' => $photoPath,
            'storage_provider' => 'local',
        ]);

        $response = $this->actingAs($admin)->get(route('attendance.photo', $event->id));
        $response->assertStatus(200);
    }
}
