<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Profile\UserProfile;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserProfileMenuTest extends TestCase
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

    public function test_guest_is_redirected_to_login_when_accessing_profile(): void
    {
        $response = $this->get('/profile');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_profil_pengguna_sidebar_menu_and_kelola_akun_in_dropdown(): void
    {
        $employee = Employee::firstOrFail();
        $user = $employee->user;
        if (! $user) {
            $user = User::factory()->create();
            $employee->update(['user_id' => $user->id]);
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);

        // Dedicated sidebar menu exists
        $response->assertSee('Profil Pengguna');
        $response->assertSee(route('profile'));

        // Dropdown contains only Kelola Akun
        $response->assertSee('Kelola Akun');

        // Other items are NOT in the dropdown
        $response->assertDontSee('Detail Profil Pegawai');
        $response->assertDontSee('Detail Profil Medis');
        $response->assertDontSee('Slip Gaji Saya');
        $response->assertDontSee('Riwayat Cuti');
    }

    public function test_user_can_access_and_render_user_profile_page(): void
    {
        $user = User::where('email', 'admin@juwitahospital.com')->firstOrFail();

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Profil Pengguna');
        $response->assertSee('Informasi Akun');
        $response->assertSee('Keamanan & Kata Sandi');

        Livewire::actingAs($user)
            ->test(UserProfile::class)
            ->assertStatus(200)
            ->assertSet('name', $user->name)
            ->assertSet('email', $user->email);
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => Hash::make('password123'),
        ]);

        Livewire::actingAs($user)
            ->test(UserProfile::class)
            ->set('name', 'Updated Name')
            ->set('email', 'updated@example.com')
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertSee('Informasi akun profil Anda berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-secret-123'),
        ]);

        Livewire::actingAs($user)
            ->test(UserProfile::class)
            ->set('currentPassword', 'old-secret-123')
            ->set('newPassword', 'new-secure-password-456')
            ->set('newPasswordConfirmation', 'new-secure-password-456')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSee('Kata sandi akun Anda berhasil diperbarui dengan aman.');

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password-456', $user->password));
    }
}
