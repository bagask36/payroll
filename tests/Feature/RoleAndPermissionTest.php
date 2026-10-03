<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_it_seeds_all_8_standard_roles_and_permissions(): void
    {
        $this->assertDatabaseCount('roles', 8);
        $this->assertDatabaseCount('permissions', 60);

        $expectedRoles = [
            'super_admin', 'hr_admin', 'hr_manager', 'supervisor',
            'payroll', 'finance', 'doctor', 'employee',
        ];

        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }
    }

    public function test_it_assigns_uuid_automatically_when_creating_user(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->uuid);
        $this->assertNotEmpty($user->uuid);
        $this->assertTrue(uuid_is_valid($user->uuid));
    }

    public function test_user_can_be_assigned_role_and_inherits_permissions(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->hasRole('payroll'));
        $this->assertFalse($user->hasPermission('payroll.calculate'));

        $user->assignRole('payroll');

        $this->assertTrue($user->hasRole('payroll'));
        $this->assertTrue($user->hasPermission('payroll.calculate'));
        $this->assertTrue(Gate::forUser($user)->allows('payroll.calculate'));
    }

    public function test_super_admin_has_unrestricted_permission_via_gate(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasPermission('any.arbitrary.permission'));
        $this->assertTrue(Gate::forUser($user)->allows('any.arbitrary.permission'));
    }

    public function test_user_without_permission_is_denied(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        $this->assertFalse($user->hasPermission('payroll.finalize'));
        $this->assertFalse(Gate::forUser($user)->allows('payroll.finalize'));
    }

    public function test_user_can_remove_and_sync_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('supervisor');
        $this->assertTrue($user->hasRole('supervisor'));

        $user->removeRole('supervisor');
        $this->assertFalse($user->hasRole('supervisor'));

        $user->syncRoles(['hr_admin', 'payroll']);
        $this->assertTrue($user->hasRole('hr_admin'));
        $this->assertTrue($user->hasRole('payroll'));
        $this->assertFalse($user->hasRole('supervisor'));
    }
}
