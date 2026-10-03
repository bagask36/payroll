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
            ->set('strNumber', 'STR-NEW-9999')
            ->set('strValidUntil', '2028-12-31')
            ->set('sipNumber', 'SIP-NEW-8888')
            ->set('sipValidUntil', '2028-12-31')
            ->call('updateCredentials')
            ->assertSet('showRenewalModal', false);

        $doctor->refresh();
        $this->assertEquals('STR-NEW-9999', $doctor->str_number);
        $this->assertEquals('2028-12-31', $doctor->str_valid_until->toDateString());
    }
}
