<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Holiday;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            OrganizationSeeder::class,
        ]);
    }

    public function test_it_seeds_organization_structure_correctly(): void
    {
        $this->assertDatabaseHas('departments', ['code' => 'DIR', 'name' => 'Direktorat Utama']);
        $this->assertDatabaseHas('departments', ['code' => 'YANMED', 'name' => 'Pelayanan Medik']);
        $this->assertDatabaseHas('units', ['code' => 'IGD', 'name' => 'Instalasi Gawat Darurat']);
        $this->assertDatabaseHas('units', ['code' => 'FAR', 'name' => 'Instalasi Farmasi']);
        $this->assertDatabaseHas('positions', ['title' => 'Dokter Jaga IGD']);
        $this->assertDatabaseHas('positions', ['title' => 'Apoteker Penanggung Jawab']);
        $this->assertDatabaseHas('holidays', ['name' => 'Tahun Baru 2026 Masehi']);
    }

    public function test_department_hierarchy_relationships(): void
    {
        $parent = Department::where('code', 'DIR')->firstOrFail();
        $child = Department::where('code', 'YANMED')->firstOrFail();

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains('id', $child->id));
    }

    public function test_department_to_unit_and_position_relationships(): void
    {
        $yanmed = Department::where('code', 'YANMED')->firstOrFail();
        $igd = Unit::where('code', 'IGD')->firstOrFail();

        $this->assertTrue($yanmed->units->contains('id', $igd->id));
        $this->assertEquals($yanmed->id, $igd->department->id);

        $position = Position::where('title', 'Dokter Jaga IGD')->firstOrFail();
        $this->assertEquals($igd->id, $position->unit->id);
        $this->assertTrue($igd->positions->contains('id', $position->id));
    }

    public function test_scope_active_filters_inactive_departments_and_units(): void
    {
        Department::factory()->create(['is_active' => false]);
        Unit::factory()->create(['is_active' => false]);

        $activeDepartments = Department::active()->get();
        $activeUnits = Unit::active()->get();

        $this->assertTrue($activeDepartments->every(fn (Department $d) => $d->is_active));
        $this->assertTrue($activeUnits->every(fn (Unit $u) => $u->is_active));
    }

    public function test_restricts_deletion_of_department_with_associated_units(): void
    {
        $department = Department::where('code', 'YANMED')->firstOrFail();

        $this->expectException(QueryException::class);
        $department->delete();
    }

    public function test_restricts_deletion_of_unit_with_associated_positions(): void
    {
        $unit = Unit::where('code', 'IGD')->firstOrFail();

        $this->expectException(QueryException::class);
        $unit->delete();
    }

    public function test_holiday_helper_methods_identify_holidays_and_collective_leaves(): void
    {
        $this->assertTrue(Holiday::isHoliday('2026-01-01'));
        $this->assertFalse(Holiday::isCollectiveLeave('2026-01-01'));

        $this->assertTrue(Holiday::isHoliday('2026-03-23'));
        $this->assertTrue(Holiday::isCollectiveLeave('2026-03-23'));

        $this->assertFalse(Holiday::isHoliday('2026-03-10'));
        $this->assertFalse(Holiday::isCollectiveLeave('2026-03-10'));
    }

    public function test_policy_authorization_for_organization_actions(): void
    {
        $dept = Department::where('code', 'YANMED')->firstOrFail();

        // 1. Super Admin: full access
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', Department::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $dept));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', Department::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $dept));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $dept));

        // 2. HR Manager: can view, create, update, delete
        $hrManager = User::factory()->create();
        $hrManager->assignRole('hr_manager');

        $this->assertTrue(Gate::forUser($hrManager)->allows('viewAny', Department::class));
        $this->assertTrue(Gate::forUser($hrManager)->allows('create', Department::class));
        $this->assertTrue(Gate::forUser($hrManager)->allows('update', $dept));
        $this->assertTrue(Gate::forUser($hrManager)->allows('delete', $dept));

        // 3. HR Admin: can view, create, update, but cannot delete
        $hrAdmin = User::factory()->create();
        $hrAdmin->assignRole('hr_admin');

        $this->assertTrue(Gate::forUser($hrAdmin)->allows('viewAny', Department::class));
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('create', Department::class));
        $this->assertTrue(Gate::forUser($hrAdmin)->allows('update', $dept));
        $this->assertFalse(Gate::forUser($hrAdmin)->allows('delete', $dept));

        // 4. Employee: can view, but cannot create, update, or delete
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $this->assertTrue(Gate::forUser($employee)->allows('viewAny', Department::class));
        $this->assertTrue(Gate::forUser($employee)->allows('view', $dept));
        $this->assertFalse(Gate::forUser($employee)->allows('create', Department::class));
        $this->assertFalse(Gate::forUser($employee)->allows('update', $dept));
        $this->assertFalse(Gate::forUser($employee)->allows('delete', $dept));
    }
}
