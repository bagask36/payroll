<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_authenticated_employee_sees_detail_profile_menu_and_is_redirected_to_employee_profile(): void
    {
        $employee = Employee::firstOrFail();
        $user = $employee->user;
        if (! $user) {
            $user = User::factory()->create();
            $employee->update(['user_id' => $user->id]);
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Detail Profil Pegawai');
        $response->assertSee(route('employees.show', $employee->id));

        // Test /profile redirection
        $profileResponse = $this->actingAs($user)->get('/profile');
        $profileResponse->assertRedirect(route('employees.show', $employee->id));

        // Test the target page is accessible by the user
        $showResponse = $this->actingAs($user)->get(route('employees.show', $employee->id));
        $showResponse->assertStatus(200);
    }

    public function test_authenticated_doctor_without_employee_sees_detail_profil_medis_and_is_redirected(): void
    {
        $doctor = Doctor::whereNull('employee_id')->first();
        if (! $doctor) {
            $doctor = Doctor::firstOrFail();
            $doctor->update(['employee_id' => null]);
        }

        $user = $doctor->user;
        if (! $user) {
            $user = User::factory()->create();
            $doctor->update(['user_id' => $user->id]);
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Detail Profil Medis');
        $response->assertSee(route('doctors.show', $doctor->id));

        // Test /profile redirection
        $profileResponse = $this->actingAs($user)->get('/profile');
        $profileResponse->assertRedirect(route('doctors.show', $doctor->id));

        // Test doctor can view their own profile
        $showResponse = $this->actingAs($user)->get(route('doctors.show', $doctor->id));
        $showResponse->assertStatus(200);
    }

    public function test_super_admin_without_employee_can_access_profile_redirect(): void
    {
        $adminRole = Role::where('name', 'super_admin')->firstOrFail();
        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole->id);

        $response = $this->actingAs($adminUser)->get('/profile');
        $response->assertRedirect(route('employees.index'));
    }
}
