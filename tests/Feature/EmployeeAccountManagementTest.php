<?php

namespace Tests\Feature;

use App\Livewire\Employee\EmployeeIndex;
use App\Livewire\Employee\EmployeeShow;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_employee_creation_automatically_provisions_user_account(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('hr_admin');

        $unit = Unit::factory()->create();
        $position = Position::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EmployeeIndex::class)
            ->call('openCreateModal')
            ->set('nip', 'PEG-888001')
            ->set('name', 'Anisa Triastuti, S.Kep')
            ->set('email', 'anisa.tri@rsjuwita.co.id')
            ->set('phone', '081234567888')
            ->set('gender', 'female')
            ->set('birthPlace', 'Surabaya')
            ->set('birthDate', '1996-08-20')
            ->set('ktpNumber', '3578012008960001')
            ->set('unitId', $unit->id)
            ->set('positionId', $position->id)
            ->set('contractType', 'permanent')
            ->set('joinDate', Carbon::today()->toDateString())
            ->call('createEmployee')
            ->assertHasNoErrors()
            ->assertSee('berhasil didaftarkan bersama akun pengguna');

        // Verify User was created
        $user = User::where('email', 'anisa.tri@rsjuwita.co.id')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Anisa Triastuti, S.Kep', $user->name);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertTrue($user->hasRole('employee'));

        // Verify Employee is linked to User
        $employee = Employee::where('nip', 'PEG-888001')->first();
        $this->assertNotNull($employee);
        $this->assertEquals($user->id, $employee->user_id);

        // Verify newly created user can log in
        $this->assertTrue(auth()->attempt([
            'email' => 'anisa.tri@rsjuwita.co.id',
            'password' => 'Password123!',
        ]));
    }

    public function test_employee_creation_without_email_generates_nip_based_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('hr_admin');

        $unit = Unit::factory()->create();
        $position = Position::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EmployeeIndex::class)
            ->call('openCreateModal')
            ->set('nip', 'PEG-888002')
            ->set('name', 'Bambang Sudarsono')
            ->set('email', '')
            ->set('phone', '081234567889')
            ->set('gender', 'male')
            ->set('birthPlace', 'Semarang')
            ->set('birthDate', '1992-03-15')
            ->set('ktpNumber', '3374011503920002')
            ->set('unitId', $unit->id)
            ->set('positionId', $position->id)
            ->set('contractType', 'contract')
            ->set('joinDate', Carbon::today()->toDateString())
            ->call('createEmployee')
            ->assertHasNoErrors();

        $user = User::where('email', 'peg888002@juwitahospital.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('employee'));

        $employee = Employee::where('nip', 'PEG-888002')->first();
        $this->assertEquals($user->id, $employee->user_id);
    }

    public function test_hr_admin_can_upload_and_delete_profile_photo(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('hr_admin');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Kusuma Wardani',
        ]);

        $this->actingAs($admin);

        $fakePhoto = UploadedFile::fake()->image('profile.jpg', 400, 400);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->set('avatarUpload', $fakePhoto)
            ->call('uploadAvatar')
            ->assertHasNoErrors()
            ->assertSet('accountFeedback', 'Foto profil berhasil diperbarui.');

        $user->refresh();
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);

        // Delete photo
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('deleteAvatar')
            ->assertHasNoErrors()
            ->assertSet('accountFeedback', 'Foto profil berhasil dihapus.');

        $user->refresh();
        $this->assertNull($user->profile_photo_path);
    }

    public function test_hr_admin_can_reset_employee_password(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('hr_admin');

        $user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('OldPassword123!'),
        ]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('setTab', 'account')
            ->set('newPassword', 'NewSecret2026!')
            ->set('newPasswordConfirmation', 'NewSecret2026!')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertSee('berhasil direset');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecret2026!', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset_by_admin',
        ]);
    }

    public function test_hr_admin_can_update_roles_and_privilege_escalation_is_blocked(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('hr_admin');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin);

        // 1. Normal role update (add supervisor)
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('setTab', 'account')
            ->set('selectedRoles', ['employee', 'supervisor'])
            ->call('updateRoles')
            ->assertHasNoErrors()
            ->assertSet('accountFeedback', 'Peran / Role akun berhasil diperbarui.');

        $user->refresh();
        $this->assertTrue($user->hasRole('employee'));
        $this->assertTrue($user->hasRole('supervisor'));

        // 2. Privilege escalation: HR Admin attempting to grant 'super_admin' must fail
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('setTab', 'account')
            ->set('selectedRoles', ['employee', 'super_admin'])
            ->call('updateRoles')
            ->assertSet('accountError', 'Hanya Super Administrator yang dapat menetapkan role Super Admin.');

        $user->refresh();
        $this->assertFalse($user->hasRole('super_admin'));
    }

    public function test_super_admin_can_grant_super_admin_role(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('super_admin');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($superAdmin);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('setTab', 'account')
            ->set('selectedRoles', ['employee', 'super_admin'])
            ->call('updateRoles')
            ->assertHasNoErrors()
            ->assertSet('accountFeedback', 'Peran / Role akun berhasil diperbarui.');

        $user->refresh();
        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_hr_admin_can_toggle_employee_user_status(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('hr_admin');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin);

        // Deactivate
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('setTab', 'account')
            ->call('toggleUserStatus')
            ->assertHasNoErrors()
            ->assertSet('accountFeedback', 'Status akun pengguna berhasil dinonaktifkan.');

        $user->refresh();
        $this->assertFalse($user->is_active);

        // Activate back
        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('setTab', 'account')
            ->call('toggleUserStatus')
            ->assertHasNoErrors()
            ->assertSet('accountFeedback', 'Status akun pengguna berhasil diaktifkan.');

        $user->refresh();
        $this->assertTrue($user->is_active);
    }

    public function test_provision_user_account_for_unlinked_employee(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('hr_admin');

        $employee = Employee::factory()->create([
            'user_id' => null,
            'nip' => 'PEG-777001',
            'full_name' => 'Wahyudi Santoso',
            'email' => null,
        ]);

        $this->actingAs($admin);

        Livewire::test(EmployeeShow::class, ['id' => $employee->id])
            ->call('setTab', 'account')
            ->call('provisionUserAccount')
            ->assertHasNoErrors()
            ->assertSee('berhasil dibuat dengan kata sandi awal');

        $employee->refresh();
        $this->assertNotNull($employee->user_id);
        $this->assertNotNull($employee->user);
        $this->assertEquals('peg777001@juwitahospital.com', $employee->user->email);
        $this->assertTrue($employee->user->hasRole('employee'));
    }
}
