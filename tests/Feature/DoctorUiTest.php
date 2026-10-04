<?php

namespace Tests\Feature;

use App\Livewire\Doctor\DoctorIndex;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorUiTest extends TestCase
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

    public function test_authorized_user_can_view_doctor_directory_page(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();

        $response = $this->actingAs($admin)->get('/doctors');
        $response->assertStatus(200);
        $response->assertSee('Direktori Tenaga Medis');
        $response->assertSee('dr. Hendra Pratama, Sp.PD');
        $response->assertSee('Dokter Organik');

        Livewire::actingAs($admin)
            ->test(DoctorIndex::class)
            ->assertStatus(200);
    }

    public function test_filter_and_search_doctors_in_table(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Tables\DoctorTable::class)
            ->assertStatus(200)
            ->assertSee('dr. Hendra Pratama, Sp.PD')
            ->set('search', 'Hendra')
            ->assertSee('dr. Hendra Pratama, Sp.PD');
    }

    public function test_hr_admin_can_update_doctor_credentials(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();
        $doctor = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(DoctorIndex::class)
            ->call('openRenewalModal', $doctor->id)
            ->assertSet('showRenewalModal', true)
            ->set('doctorEmail', 'dr.hendra.updated@rsjuwita.co.id')
            ->set('doctorPhone', '081299990000')
            ->set('strNumber', 'STR-NEW-9999')
            ->set('strValidUntil', '2028-12-31')
            ->set('sipNumber', 'SIP-NEW-8888')
            ->set('sipValidUntil', '2028-12-31')
            ->call('updateCredentials')
            ->assertHasNoErrors()
            ->assertSet('showRenewalModal', false);

        $doctor->refresh();
        $this->assertEquals('dr.hendra.updated@rsjuwita.co.id', $doctor->email);
        $this->assertEquals('081299990000', $doctor->phone_number);
        $this->assertEquals('STR-NEW-9999', $doctor->str_number);
        $this->assertEquals('2028-12-31', $doctor->str_valid_until->toDateString());
    }

    public function test_hr_admin_can_register_new_doctor_with_email(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();

        Livewire::actingAs($admin)
            ->test(DoctorIndex::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->set('newName', 'dr. Farhan Syahputra, Sp.A')
            ->set('newSpecialization', 'Spesialis Anak')
            ->set('newEmail', 'dr.farhan@rsjuwita.co.id')
            ->set('newPhone', '081122334455')
            ->set('newStrNumber', 'STR-FARHAN-01')
            ->set('newStrValidUntil', '2029-01-01')
            ->set('newSipNumber', 'SIP-FARHAN-01')
            ->set('newSipValidUntil', '2029-01-01')
            ->call('createDoctor')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('doctors', [
            'name' => 'dr. Farhan Syahputra, Sp.A',
            'specialization' => 'Spesialis Anak',
            'email' => 'dr.farhan@rsjuwita.co.id',
            'phone_number' => '081122334455',
            'str_number' => 'STR-FARHAN-01',
            'sip_number' => 'SIP-FARHAN-01',
        ]);
    }
}
