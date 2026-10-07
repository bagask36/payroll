<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\DoctorTable;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorTableTest extends TestCase
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

    public function test_powergrid_doctor_table_renders_data(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();

        $this->actingAs($admin);

        $doctor = Doctor::where('name', 'dr. Hendra Pratama, Sp.PD')->firstOrFail();
        $table = new DoctorTable();
        $actions = $table->actions($doctor);
        $actionSlots = array_map(fn ($act) => $act->slot, $actions);
        $this->assertContains('Lihat Profil', $actionSlots);
        $this->assertNotContains('Perbarui Kredensial', $actionSlots);

        Livewire::test(DoctorTable::class)
            ->assertStatus(200)
            ->assertSee('dr. Hendra Pratama, Sp.PD')
            ->assertSee('Dokter Organik')
            ->assertSee(route('doctors.show', $doctor->id));
    }

    public function test_doctor_table_search(): void
    {
        $admin = User::where('email', 'admin@juwitahospital.com')->first();

        $this->actingAs($admin);

        Livewire::test(DoctorTable::class)
            ->set('search', 'Hendra')
            ->assertSee('dr. Hendra Pratama, Sp.PD');
    }
}
