<?php

namespace Tests\Feature;

use App\Livewire\Attendance\AttendanceIndex;
use App\Livewire\Employee\EmployeeShow;
use App\Models\Employee;
use App\Models\FaceBiometricRegistration;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FaceBiometricRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_employee_can_submit_face_registration_request_from_attendance(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'face_biometric_vector' => null,
        ]);

        $this->actingAs($user);

        // Fake image content
        $fakeImage = UploadedFile::fake()->image('selfie.jpg', 400, 400);

        Livewire::test(AttendanceIndex::class)
            ->assertSee('Daftarkan Wajah Presensi')
            ->call('openFaceRegistrationModal')
            ->assertSet('showFaceRegistrationModal', true)
            ->set('facePhotoUpload', $fakeImage)
            ->set('faceRegistrationNotes', 'Pendaftaran wajah awal staf laboratorium')
            ->call('submitFaceRegistration')
            ->assertSet('showFaceRegistrationModal', false)
            ->assertSee('Pengajuan pendaftaran biometrik wajah berhasil dikirimkan');

        $this->assertDatabaseHas('face_biometric_registrations', [
            'employee_id' => $employee->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'notes' => 'Pendaftaran wajah awal staf laboratorium',
        ]);

        $registration = FaceBiometricRegistration::first();
        $this->assertTrue(Storage::disk('local')->exists($registration->photo_path));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.face_registration_requested',
        ]);
    }

    public function test_hr_can_view_submitted_photo_while_unauthorized_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $photoPath = 'biometrics/registrations/test.jpg';
        Storage::disk('local')->put($photoPath, 'fake-jpeg-content');

        $registration = FaceBiometricRegistration::create([
            'employee_id' => $employee->id,
            'requested_by' => $user->id,
            'photo_path' => $photoPath,
            'status' => 'pending',
        ]);

        // 1. Another unrelated employee cannot view the photo
        $otherUser = User::factory()->create(['is_active' => true]);
        $otherUser->assignRole('employee');
        Employee::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($otherUser)
            ->get(route('biometrics.registrations.photo', $registration->id))
            ->assertForbidden();

        // 2. The owner employee can view their own photo
        $this->actingAs($user)
            ->get(route('biometrics.registrations.photo', $registration->id))
            ->assertOk();

        // 3. HR Admin can view the photo
        $hrUser = User::factory()->create(['is_active' => true]);
        $hrUser->assignRole('hr_admin');

        $this->actingAs($hrUser)
            ->get(route('biometrics.registrations.photo', $registration->id))
            ->assertOk();
    }

    public function test_hr_can_approve_face_registration_on_biometrics_tab(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'face_biometric_vector' => null,
        ]);

        $photoPath = 'biometrics/registrations/test_approval.jpg';
        Storage::disk('local')->put($photoPath, 'fake-image-bytes');

        $registration = FaceBiometricRegistration::create([
            'employee_id' => $employee->id,
            'requested_by' => $user->id,
            'photo_path' => $photoPath,
            'status' => 'pending',
            'notes' => 'Mohon disetujui segera',
        ]);

        $hrUser = User::factory()->create(['is_active' => true]);
        $hrUser->assignRole('hr_admin');

        $this->actingAs($hrUser);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('activeTab', 'biometrics')
            ->assertSee('Permohonan Pendaftaran Biometrik Wajah (Menunggu Persetujuan HRD)')
            ->assertSee('Setujui &amp; Aktifkan Biometrik', false)
            ->call('approveFaceRegistration', $registration->id)
            ->assertSee('Pengajuan biometrik wajah berhasil disetujui');

        $registration->refresh();
        $this->assertEquals('approved', $registration->status);
        $this->assertEquals($hrUser->id, $registration->reviewed_by);
        $this->assertNotNull($registration->reviewed_at);

        $employee->refresh();
        $this->assertNotEmpty($employee->face_biometric_vector);
        $this->assertIsArray($employee->face_biometric_vector);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.face_registration_approved',
        ]);
    }

    public function test_hr_can_reject_face_registration_with_reason(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $photoPath = 'biometrics/registrations/test_rejection.jpg';
        Storage::disk('local')->put($photoPath, 'fake-image-bytes');

        $registration = FaceBiometricRegistration::create([
            'employee_id' => $employee->id,
            'requested_by' => $user->id,
            'photo_path' => $photoPath,
            'status' => 'pending',
        ]);

        $hrUser = User::factory()->create(['is_active' => true]);
        $hrUser->assignRole('hr_admin');

        $this->actingAs($hrUser);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('activeTab', 'biometrics')
            ->call('openRejectRegistrationModal', $registration->id)
            ->assertSet('showRejectRegistrationModal', true)
            ->set('registrationRejectionReason', 'Foto wajah terlalu gelap dan tidak fokus')
            ->call('rejectFaceRegistration')
            ->assertSet('showRejectRegistrationModal', false)
            ->assertSee('Pengajuan pendaftaran biometrik wajah telah ditolak');

        $registration->refresh();
        $this->assertEquals('rejected', $registration->status);
        $this->assertEquals('Foto wajah terlalu gelap dan tidak fokus', $registration->rejection_reason);
        $this->assertEquals($hrUser->id, $registration->reviewed_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee.face_registration_rejected',
        ]);
    }

    public function test_regular_employee_cannot_approve_or_reject_registration(): void
    {
        $userA = User::factory()->create(['is_active' => true]);
        $userA->assignRole('employee');
        $employeeA = Employee::factory()->create(['user_id' => $userA->id]);

        $photoPath = 'biometrics/registrations/test_guard.jpg';
        Storage::disk('local')->put($photoPath, 'fake-image-bytes');

        $registration = FaceBiometricRegistration::create([
            'employee_id' => $employeeA->id,
            'requested_by' => $userA->id,
            'photo_path' => $photoPath,
            'status' => 'pending',
        ]);

        $userB = User::factory()->create(['is_active' => true]);
        $userB->assignRole('employee');

        // User without employee.view cannot view other employee show page
        $this->actingAs($userB);
        Livewire::test(EmployeeShow::class, ['id' => $employeeA->id])
            ->assertForbidden();

        // User with supervisor role can view employee but cannot manage biometrics
        $supervisor = User::factory()->create(['is_active' => true]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor);
        Livewire::test(EmployeeShow::class, ['id' => $employeeA->id])
            ->assertOk()
            ->call('approveFaceRegistration', $registration->id)
            ->assertForbidden();
    }
}
