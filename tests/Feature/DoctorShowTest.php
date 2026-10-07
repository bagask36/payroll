<?php

namespace Tests\Feature;

use App\Livewire\Doctor\DoctorShow;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorShowTest extends TestCase
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

    public function test_authorized_user_can_access_doctor_profile_page(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $doctor = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->firstOrFail();

        $response = $this->actingAs($admin)->get("/doctors/{$doctor->id}");
        $response->assertStatus(200);
        $response->assertSee('dr. Hendra Pratama, Sp.PD');
        $response->assertSee('Spesialis Penyakit Dalam');
        $response->assertSee('Dokter Organik RS');
        $response->assertSee('Kredensial Lisensi Medis');

        Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->assertStatus(200)
            ->assertSee($doctor->name);
    }

    public function test_unauthenticated_user_cannot_access_doctor_profile(): void
    {
        $doctor = Doctor::firstOrFail();

        $response = $this->get("/doctors/{$doctor->id}");
        $response->assertRedirect('/login');
    }

    public function test_tab_switching_works(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $doctor = Doctor::firstOrFail();

        Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->assertSet('activeTab', 'bio')
            ->call('setTab', 'schedules')
            ->assertSet('activeTab', 'schedules')
            ->call('setTab', 'attendance')
            ->assertSet('activeTab', 'attendance')
            ->call('setTab', 'leave')
            ->assertSet('activeTab', 'leave')
            ->call('setTab', 'documents')
            ->assertSet('activeTab', 'documents')
            ->call('setTab', 'account')
            ->assertSet('activeTab', 'account');
    }

    public function test_hr_admin_can_update_doctor_bio(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $doctor = Doctor::firstOrFail();

        Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->call('openBioModal')
            ->assertSet('showBioModal', true)
            ->set('bioName', 'dr. Hendra Pratama, Sp.PD, K-GEH')
            ->set('bioSpecialization', 'Spesialis Gastroenterologi Hepatologi')
            ->set('bioPhone', '081288889999')
            ->call('saveBio')
            ->assertHasNoErrors()
            ->assertSet('showBioModal', false);

        $doctor->refresh();
        $this->assertEquals('dr. Hendra Pratama, Sp.PD, K-GEH', $doctor->name);
        $this->assertEquals('Spesialis Gastroenterologi Hepatologi', $doctor->specialization);
        $this->assertEquals('081288889999', $doctor->phone_number);
    }

    public function test_hr_admin_can_update_str_and_sip_credentials(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $doctor = Doctor::firstOrFail();

        Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->call('openCredentialModal')
            ->assertSet('showCredentialModal', true)
            ->set('strNumber', 'STR-UPDATED-2026')
            ->set('strValidUntil', '2030-05-15')
            ->set('sipNumber', 'SIP-UPDATED-2026')
            ->set('sipValidUntil', '2030-05-15')
            ->call('saveCredentials')
            ->assertHasNoErrors()
            ->assertSet('showCredentialModal', false);

        $doctor->refresh();
        $this->assertEquals('STR-UPDATED-2026', $doctor->str_number);
        $this->assertEquals('2030-05-15', $doctor->str_valid_until->toDateString());
        $this->assertEquals('SIP-UPDATED-2026', $doctor->sip_number);
        $this->assertEquals('2030-05-15', $doctor->sip_valid_until->toDateString());
    }

    public function test_hr_admin_can_link_and_unlink_organic_employee(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        // Pick partner doctor (no employee linked)
        $doctor = Doctor::whereNull('employee_id')->first() ?? Doctor::firstOrFail();
        $employee = Employee::whereDoesntHave('doctor')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->call('openLinkModal')
            ->assertSet('showLinkModal', true)
            ->set('selectedEmployeeId', $employee->id)
            ->call('saveLinkEmployee')
            ->assertHasNoErrors()
            ->assertSet('showLinkModal', false);

        $doctor->refresh();
        $this->assertEquals($employee->id, $doctor->employee_id);

        // Now unlink
        Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->set('selectedEmployeeId', null)
            ->call('saveLinkEmployee')
            ->assertHasNoErrors();

        $doctor->refresh();
        $this->assertNull($doctor->employee_id);
    }

    public function test_hr_admin_can_upload_and_delete_document(): void
    {
        Storage::fake('local');
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $doctor = Doctor::firstOrFail();

        $file = UploadedFile::fake()->create('str_scan.pdf', 500, 'application/pdf');

        $component = Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->set('docUploadType', 'str')
            ->set('docUploadedFile', $file)
            ->call('uploadDoc')
            ->assertHasNoErrors();

        $doctor->refresh();
        $this->assertCount(1, $doctor->documents);
        $doc = $doctor->documents->first();
        $this->assertEquals('str', $doc->document_type);
        $this->assertEquals('str_scan.pdf', $doc->original_name);

        // Delete document
        $component->call('deleteDocument', $doc->id)
            ->assertHasNoErrors();

        $doctor->refresh();
        $this->assertCount(0, $doctor->documents);
    }

    public function test_hr_admin_can_create_portal_account_and_reset_password(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $doctor = Doctor::whereNull('user_id')->first() ?? Doctor::create([
            'name' => 'dr. Akun Baru, Sp.B',
            'specialization' => 'Spesialis Bedah',
            'str_number' => 'STR-BEDAH-99',
            'str_valid_until' => '2028-01-01',
            'sip_number' => 'SIP-BEDAH-99',
            'sip_valid_until' => '2028-01-01',
            'email' => 'dr.bedah.baru@rsjuwita.co.id',
            'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->set('accountEmail', 'portal.dr.bedah@rsjuwita.co.id')
            ->call('createPortalAccount')
            ->assertHasNoErrors()
            ->assertSet('activeTab', 'bio');

        $doctor->refresh();
        $this->assertNotNull($doctor->user_id);
        $this->assertEquals('portal.dr.bedah@rsjuwita.co.id', $doctor->user->email);
        $this->assertTrue($doctor->user->hasRole('doctor'));

        // Reset password
        Livewire::actingAs($admin)
            ->test(DoctorShow::class, ['id' => $doctor->id])
            ->set('newPassword', 'NewSecret123!')
            ->set('newPasswordConfirmation', 'NewSecret123!')
            ->call('resetUserPassword')
            ->assertHasNoErrors();
    }
}
