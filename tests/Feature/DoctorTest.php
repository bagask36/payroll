<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DoctorTest extends TestCase
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
        ]);
    }

    public function test_it_seeds_organic_and_partner_doctors(): void
    {
        // 1. Organic doctor with employee link
        $organicDoc = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->firstOrFail();
        $this->assertNotNull($organicDoc->user);
        $this->assertNotNull($organicDoc->employee);
        $this->assertEquals('DOK-2023001', $organicDoc->employee->nip);
        $this->assertEquals($organicDoc->id, $organicDoc->user->doctor->id);
        $this->assertEquals($organicDoc->id, $organicDoc->employee->doctor->id);

        // 2. Partner doctor without employee link
        $partnerDoc = Doctor::where('name', 'dr. Citra Anindya, Sp.A')->firstOrFail();
        $this->assertNotNull($partnerDoc->user);
        $this->assertNull($partnerDoc->employee);
    }

    public function test_it_generates_uuid_automatically(): void
    {
        $doctor = Doctor::factory()->create();

        $this->assertNotNull($doctor->uuid);
        $this->assertTrue(uuid_is_valid($doctor->uuid));
    }

    public function test_credentials_expiration_helper_methods(): void
    {
        // Expired STR
        $expiredDoc = Doctor::factory()->create([
            'str_valid_until' => Carbon::yesterday(),
            'sip_valid_until' => Carbon::tomorrow(),
        ]);
        $this->assertTrue($expiredDoc->isStrExpired());
        $this->assertFalse($expiredDoc->isSipExpired());

        // Expiring soon (within 30 days)
        $expiringSoonDoc = Doctor::factory()->create([
            'str_valid_until' => Carbon::today()->addDays(15),
            'sip_valid_until' => Carbon::today()->addYears(2),
        ]);
        $this->assertFalse($expiringSoonDoc->isStrExpired());
        $this->assertTrue($expiringSoonDoc->isExpiringSoon(30));
        $this->assertFalse($expiringSoonDoc->isExpiringSoon(10));
    }

    public function test_it_supports_soft_deletes(): void
    {
        $doctor = Doctor::firstOrFail();
        $doctor->delete();

        $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
        $this->assertNull(Doctor::find($doctor->id));
        $this->assertNotNull(Doctor::withTrashed()->find($doctor->id));
    }

    public function test_doctor_policy_view_directory(): void
    {
        $doctor = Doctor::firstOrFail();

        // Any authenticated user with doctor.view can view
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $this->assertTrue(Gate::forUser($employee)->allows('viewAny', Doctor::class));
        $this->assertTrue(Gate::forUser($employee)->allows('view', $doctor));
    }

    public function test_doctor_policy_view_and_manage_credentials(): void
    {
        $doctor = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->firstOrFail();
        $doctorUser = $doctor->user;

        // 1. Doctor viewing their OWN credentials: ALLOWED
        $this->assertTrue(Gate::forUser($doctorUser)->allows('viewCredentials', $doctor));
        $this->assertTrue(Gate::forUser($doctorUser)->allows('manageCredentials', $doctor));

        // 2. HR Admin: ALLOWED
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('hr_admin');
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('viewCredentials', $doctor));
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('manageCredentials', $doctor));

        // 3. HR Manager: ALLOWED
        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');
        $this->assertTrue(Gate::forUser($hrManager)->allows('viewCredentials', $doctor));
        $this->assertTrue(Gate::forUser($hrManager)->allows('manageCredentials', $doctor));

        // 4. Another Doctor viewing someone else's credentials: DENIED
        $otherDoctorUser = Doctor::where('name', 'dr. Citra Anindya, Sp.A')->firstOrFail()->user;
        $this->assertFalse(Gate::forUser($otherDoctorUser)->allows('viewCredentials', $doctor));
        $this->assertFalse(Gate::forUser($otherDoctorUser)->allows('manageCredentials', $doctor));

        // 5. Regular employee: DENIED
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $this->assertFalse(Gate::forUser($employee)->allows('viewCredentials', $doctor));
        $this->assertFalse(Gate::forUser($employee)->allows('manageCredentials', $doctor));
    }

    public function test_doctor_policy_deactivation(): void
    {
        $doctor = Doctor::firstOrFail();

        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('hr_admin');

        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');

        // HR Admin cannot deactivate doctor
        $this->assertFalse(Gate::forUser($hrAdmin)->allows('deactivate', $doctor));

        // HR Manager CAN deactivate doctor
        $this->assertTrue(Gate::forUser($hrManager)->allows('deactivate', $doctor));
    }
}
