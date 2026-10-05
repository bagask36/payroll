<?php

namespace Tests\Feature;

use App\Contracts\FaceAiService;
use App\DTOs\FaceAi\FaceVerificationResult;
use App\DTOs\FaceAi\LivenessResult;
use App\Jobs\UploadAttendancePhotoToGoogleDrive;
use App\Livewire\Attendance\AttendanceIndex;
use App\Livewire\Attendance\ClockInOut;
use App\Models\Attendance;
use App\Models\AttendanceAdjustment;
use App\Models\Employee;
use App\Models\GeofenceLocation;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        // Seed RS Juwita Main Geofence Location
        GeofenceLocation::factory()->create([
            'name' => 'RS Juwita Kampus Utama',
            'latitude' => -6.195000,
            'longitude' => 106.820000,
            'radius_meters' => 100,
            'is_active' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_access_clock_in_out(): void
    {
        $user = User::factory()->create(['is_active' => true]); // No roles or permissions

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->assertForbidden();
    }

    public function test_authorized_employee_can_view_clock_in_out(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->assertStatus(200)
            ->assertSee('Presensi Mandiri Terverifikasi');
    }

    public function test_clock_in_outside_geofence_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user);

        // Coordinates far away (e.g. -6.250000, 106.900000 > 5km away)
        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.250000, 106.900000)
            ->assertSet('isWithinGeofence', false)
            ->call('clockIn')
            ->assertSee('Posisi Anda berada di luar radius rumah sakit');

        $this->assertDatabaseMissing('attendances', [
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_clock_in_inside_geofence_succeeds_and_records_event(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        // Scheduled shift starting in 10 minutes (so employee is on time)
        $shift = Shift::factory()->create([
            'start_time' => Carbon::now()->addMinutes(10)->format('H:i:s'),
            'name' => 'Shift Sore',
        ]);
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_date' => Carbon::today()->toDateString(),
        ]);

        $this->actingAs($user);

        // Exact coordinates of RS Juwita
        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->assertSet('isWithinGeofence', true)
            ->call('clockIn')
            ->assertSee('Clock In berhasil dicatat');

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals('present', $attendance->status);

        $this->assertDatabaseHas('attendance_events', [
            'event_type' => 'clock_in',
            'is_within_geofence' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.clock_in',
        ]);
    }

    public function test_clock_out_calculates_work_duration(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        // Create an existing clock in 8 hours ago
        $clockInTime = Carbon::now()->subHours(8);
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => $clockInTime,
            'clock_out' => null,
            'status' => 'present',
        ]);

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->call('clockOut')
            ->assertSee('Clock Out berhasil dicatat');

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $this->assertGreaterThanOrEqual(470, $attendance->work_duration_minutes); // ~480 mins

        $this->assertDatabaseHas('attendance_events', [
            'attendance_id' => $attendance->id,
            'event_type' => 'clock_out',
        ]);
    }

    public function test_attendance_adjustment_request_and_approval_flow(): void
    {
        // 1. Employee creates adjustment request
        $employeeUser = User::factory()->create(['is_active' => true]);
        $employeeUser->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'status' => 'ACTIVE',
        ]);

        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->setTime(9, 30),
            'clock_out' => null,
            'status' => 'late',
        ]);

        $this->actingAs($employeeUser);

        Livewire::test(AttendanceIndex::class)
            ->call('openAdjustmentModal', $attendance->id)
            ->set('adjustedClockIn', '08:00')
            ->set('adjustedClockOut', '16:00')
            ->set('adjustmentReason', 'Perangkat scanner presensi sempat error di lobi utama')
            ->call('submitAdjustment')
            ->assertSee('Pengajuan koreksi presensi berhasil dikirimkan ke atasan');

        $adjustment = AttendanceAdjustment::where('attendance_id', $attendance->id)->firstOrFail();
        $this->assertEquals('pending', $adjustment->status);

        // 2. Supervisor approves adjustment
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);

        Livewire::test(AttendanceIndex::class)
            ->call('approveAdjustment', $adjustment->id)
            ->assertSee('Koreksi presensi berhasil disetujui');

        $adjustment->refresh();
        $this->assertEquals('approved', $adjustment->status);
        $this->assertEquals($supervisor->id, $adjustment->approved_by);

        $attendance->refresh();
        $this->assertEquals('08:00:00', $attendance->clock_in->toTimeString());
        $this->assertEquals('16:00:00', $attendance->clock_out->toTimeString());
        $this->assertEquals(480, $attendance->work_duration_minutes);
    }

    public function test_active_liveness_challenge_can_be_generated(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user);

        $test = Livewire::test(ClockInOut::class)
            ->assertSet('isWithinGeofence', false);

        $this->assertNotNull($test->get('challengeType'));
        $this->assertNotNull($test->get('challengeToken'));

        $test->call('generateChallenge');
        $this->assertNotNull($test->get('challengeToken'));
    }

    public function test_clock_in_with_face_liveness_succeeds_and_records_scores(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'face_biometric_vector' => array_fill(0, 128, 0.05),
        ]);

        $shift = Shift::factory()->create([
            'start_time' => Carbon::now()->addMinutes(15)->format('H:i:s'),
            'name' => 'Shift Pagi',
        ]);
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_date' => Carbon::today()->toDateString(),
        ]);

        $this->actingAs($user);

        $fakeFaceBase64 = 'data:image/jpeg;base64,'.base64_encode('fake-live-face-frame');

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->set('faceImageBase64', $fakeFaceBase64)
            ->set('livenessPassed', true)
            ->call('clockIn')
            ->assertSee('Clock In berhasil dicatat');

        $this->assertDatabaseHas('attendance_events', [
            'event_type' => 'clock_in',
            'face_matched' => true,
        ]);
    }

    public function test_clock_in_rejected_when_face_liveness_fails_spoof_detection(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $shift = Shift::factory()->create([
            'start_time' => Carbon::now()->addMinutes(15)->format('H:i:s'),
            'name' => 'Shift Pagi',
        ]);
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_date' => Carbon::today()->toDateString(),
        ]);

        // Mock FaceAiService to simulate paper spoof attack
        $mockFaceAi = \Mockery::mock(FaceAiService::class);
        $mockFaceAi->shouldReceive('checkLiveness')
            ->once()
            ->andReturn(LivenessResult::spoof(0.20, 'Deteksi foto kertas/layar statis terdeteksi.'));

        $this->app->instance(FaceAiService::class, $mockFaceAi);

        $this->actingAs($user);

        $fakeFaceBase64 = 'data:image/jpeg;base64,'.base64_encode('fake-static-paper-photo');

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->set('faceImageBase64', $fakeFaceBase64)
            ->call('clockIn')
            ->assertSee('Verifikasi keaslian wajah gagal: Deteksi foto kertas/layar statis terdeteksi.');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_clock_in_rejected_when_face_biometric_mismatches(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'face_biometric_vector' => array_fill(0, 128, 0.05),
        ]);

        $shift = Shift::factory()->create([
            'start_time' => Carbon::now()->addMinutes(15)->format('H:i:s'),
            'name' => 'Shift Pagi',
        ]);
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_date' => Carbon::today()->toDateString(),
        ]);

        // Mock FaceAiService to pass liveness but fail similarity
        $mockFaceAi = \Mockery::mock(FaceAiService::class);
        $mockFaceAi->shouldReceive('checkLiveness')
            ->once()
            ->andReturn(LivenessResult::live(0.98));
        $mockFaceAi->shouldReceive('verify')
            ->once()
            ->andReturn(FaceVerificationResult::mismatched(0.42, 0.85));

        $this->app->instance(FaceAiService::class, $mockFaceAi);

        $this->actingAs($user);

        $fakeFaceBase64 = 'data:image/jpeg;base64,'.base64_encode('someone-elses-face');

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->set('faceImageBase64', $fakeFaceBase64)
            ->call('clockIn')
            ->assertSee('Wajah tidak cocok dengan data biometrik terdaftar');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_clock_in_and_clock_out_stores_compressed_photo_and_path(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $shift = Shift::factory()->create([
            'start_time' => Carbon::now()->addMinutes(15)->format('H:i:s'),
            'name' => 'Shift Pagi',
        ]);
        ScheduleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'schedule_date' => Carbon::today()->toDateString(),
        ]);

        Queue::fake([UploadAttendancePhotoToGoogleDrive::class]);
        $this->actingAs($user);

        // Generate sample valid JPEG base64
        $img = imagecreatetruecolor(120, 120);
        $color = imagecolorallocate($img, 60, 160, 240);
        imagefill($img, 0, 0, $color);
        ob_start();
        imagejpeg($img, null, 80);
        $jpegData = ob_get_clean();
        imagedestroy($img);
        $sampleFaceBase64 = 'data:image/jpeg;base64,'.base64_encode($jpegData);

        // 1. Clock In with Face Photo
        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->set('faceImageBase64', $sampleFaceBase64)
            ->set('livenessPassed', true)
            ->call('clockIn')
            ->assertSee('Clock In berhasil dicatat');

        $attendance = Attendance::with('events')->where('employee_id', $employee->id)->firstOrFail();
        $clockInEvent = $attendance->events->firstWhere('event_type', 'clock_in');

        $this->assertNotNull($clockInEvent);
        $this->assertNotNull($clockInEvent->photo_path);
        $this->assertStringContainsString('clock_in', $clockInEvent->photo_path);
        Storage::disk('public')->assertExists($clockInEvent->photo_path);

        // 2. Clock Out with Face Photo
        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->set('faceImageBase64', $sampleFaceBase64)
            ->set('livenessPassed', true)
            ->call('clockOut')
            ->assertSee('Clock Out berhasil dicatat');

        $attendance->refresh();
        $clockOutEvent = $attendance->events()->where('event_type', 'clock_out')->firstOrFail();

        $this->assertNotNull($clockOutEvent->photo_path);
        $this->assertStringContainsString('clock_out', $clockOutEvent->photo_path);
        Storage::disk('public')->assertExists($clockOutEvent->photo_path);

        // Verify background job was queued for both clock in and clock out
        Queue::assertPushed(UploadAttendancePhotoToGoogleDrive::class, 2);
    }

    public function test_authorized_user_can_view_attendance_detail_modal_with_photos(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::today()->setTime(8, 0),
            'clock_out' => Carbon::today()->setTime(16, 0),
            'status' => 'present',
        ]);

        $attendance->events()->create([
            'event_type' => 'clock_in',
            'recorded_at' => Carbon::today()->setTime(8, 0),
            'latitude' => -6.195,
            'longitude' => 106.820,
            'distance_meters' => 10,
            'is_within_geofence' => true,
            'photo_path' => 'attendance-photos/2026/10/fake_in.jpg',
            'face_matched' => true,
            'face_similarity' => 0.94,
            'liveness_score' => 0.98,
        ]);

        $this->actingAs($user);

        Livewire::test(AttendanceIndex::class)
            ->call('openDetailModal', $attendance->id)
            ->assertSet('showDetailModal', true)
            ->assertSee('Detail &amp; Bukti Foto Presensi', false)
            ->assertSee(route('attendance.photo', $attendance->events->first()->id))
            ->call('closeDetailModal')
            ->assertSet('showDetailModal', false);
    }

    public function test_unauthorized_employee_cannot_view_others_attendance_detail_modal(): void
    {
        $userA = User::factory()->create(['is_active' => true]);
        $userA->assignRole('employee');
        Employee::factory()->create(['user_id' => $userA->id, 'status' => 'ACTIVE']);

        $userB = User::factory()->create(['is_active' => true]);
        $userB->assignRole('employee');
        $employeeB = Employee::factory()->create(['user_id' => $userB->id, 'status' => 'ACTIVE']);

        $attendanceB = Attendance::factory()->create([
            'employee_id' => $employeeB->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'present',
        ]);

        // User A tries to open User B's attendance detail modal -> must be forbidden
        $this->actingAs($userA);

        Livewire::test(AttendanceIndex::class)
            ->call('openDetailModal', $attendanceB->id)
            ->assertForbidden();
    }

    public function test_clock_in_out_evicts_corrupted_geofence_cache_safely(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');
        Employee::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        // Simulate corrupted / incomplete class object stored in cache
        Cache::put('active_geofence_location', 'corrupted_or_incomplete_class_stub', 3600);

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->assertSet('isWithinGeofence', true);

        $this->assertFalse(Cache::has('active_geofence_location'));
    }

    public function test_clock_in_rejected_when_actual_facial_features_differ_from_enrolled_biometrics(): void
    {
        $faceAi = app(FaceAiService::class);

        // Person A image (enrolled face)
        $imgA = imagecreatetruecolor(100, 100);
        imagefilledrectangle($imgA, 0, 0, 99, 99, imagecolorallocate($imgA, 220, 200, 180));
        imagefilledellipse($imgA, 50, 50, 60, 70, imagecolorallocate($imgA, 240, 210, 190));
        imagefilledellipse($imgA, 40, 45, 10, 8, imagecolorallocate($imgA, 50, 40, 30));
        imagefilledellipse($imgA, 60, 45, 10, 8, imagecolorallocate($imgA, 50, 40, 30));
        ob_start();
        imagejpeg($imgA);
        $rawA = ob_get_clean();

        $enrolledVector = $faceAi->extractEmbedding($rawA)->embedding;

        // Person B image (different face trying to clock in)
        $imgB = imagecreatetruecolor(100, 100);
        imagefilledrectangle($imgB, 0, 0, 99, 99, imagecolorallocate($imgB, 50, 50, 60));
        imagefilledellipse($imgB, 50, 50, 80, 50, imagecolorallocate($imgB, 120, 90, 80));
        imagefilledellipse($imgB, 35, 40, 16, 14, imagecolorallocate($imgB, 10, 10, 10));
        imagefilledellipse($imgB, 65, 40, 16, 14, imagecolorallocate($imgB, 10, 10, 10));
        ob_start();
        imagejpeg($imgB);
        $rawB = ob_get_clean();

        $capturedBase64 = 'data:image/jpeg;base64,'.base64_encode($rawB);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'face_biometric_vector' => $enrolledVector,
        ]);

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->set('faceImageBase64', $capturedBase64)
            ->call('clockIn')
            ->assertSee('Wajah tidak cocok dengan data biometrik terdaftar');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_clock_in_rejected_when_enrolled_employee_does_not_provide_face_photo(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'face_biometric_vector' => [0.1, 0.2, 0.3],
        ]);

        $this->actingAs($user);

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->set('faceImageBase64', null)
            ->call('clockIn')
            ->assertSee('Data biometrik wajah terdaftar pada profil Anda. Wajib mengambil foto wajah untuk presensi.');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_clock_in_rejected_when_client_liveness_score_is_low(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user);

        // Generate valid jpeg image
        $img = imagecreatetruecolor(100, 100);
        ob_start();
        imagejpeg($img);
        $raw = ob_get_clean();
        $capturedBase64 = 'data:image/jpeg;base64,'.base64_encode($raw);

        Livewire::test(ClockInOut::class)
            ->call('updateCoordinates', -6.195000, 106.820000)
            ->set('faceImageBase64', $capturedBase64)
            ->set('clientLivenessScore', 0.25) // Low liveness (< 0.50 motion)
            ->call('clockIn')
            ->assertSee('Verifikasi keaslian wajah gagal');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
        ]);
    }
}
