<?php

namespace Tests\Feature;

use App\Livewire\Doctor\DoctorIndex;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unauthorized_user_cannot_access_doctor_directory(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user);

        Livewire::test(DoctorIndex::class)
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_and_filter_doctors(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('hr_admin');

        $doc1 = Doctor::factory()->create([
            'name' => 'dr. Bambang Sp.B',
            'specialization' => 'Spesialis Bedah (Sp.B)',
            'status' => 'active',
        ]);

        $doc2 = Doctor::factory()->create([
            'name' => 'dr. Dian Sp.A',
            'specialization' => 'Spesialis Anak (Sp.A)',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(DoctorIndex::class)
            ->assertStatus(200)
            ->assertSee('dr. Bambang Sp.B')
            ->assertSee('dr. Dian Sp.A')
            ->set('specializationFilter', 'Spesialis Bedah (Sp.B)')
            ->assertSee('dr. Bambang Sp.B')
            ->assertDontSee('dr. Dian Sp.A');
    }

    public function test_doctor_can_update_their_own_license_credentials(): void
    {
        $doctorUser = User::factory()->create(['is_active' => true]);
        $doctorUser->assignRole('doctor');

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'name' => 'dr. Sarah Sp.PD',
            'str_number' => 'STR-OLD-11111',
            'sip_number' => 'SIP-OLD-22222',
            'status' => 'active',
        ]);

        $this->actingAs($doctorUser);

        $newStrDate = Carbon::today()->addYears(5)->toDateString();
        $newSipDate = Carbon::today()->addYears(3)->toDateString();

        Livewire::test(DoctorIndex::class)
            ->call('openRenewalModal', $doctor->id)
            ->set('strNumber', 'STR-NEW-99999')
            ->set('strValidUntil', $newStrDate)
            ->set('sipNumber', 'SIP-NEW-88888')
            ->set('sipValidUntil', $newSipDate)
            ->call('updateCredentials')
            ->assertHasNoErrors()
            ->assertSee('berhasil diperbarui');

        $doctor->refresh();
        $this->assertEquals('STR-NEW-99999', $doctor->str_number);
        $this->assertEquals('SIP-NEW-88888', $doctor->sip_number);
        $this->assertEquals($newStrDate, $doctor->str_valid_until->toDateString());
        $this->assertEquals($newSipDate, $doctor->sip_valid_until->toDateString());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'doctor.credentials_updated',
            'auditable_id' => $doctor->id,
        ]);
    }
}
