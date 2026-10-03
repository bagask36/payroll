<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrgStructureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            OrganizationSeeder::class,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');

        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('employee');
    }

    public function test_authorized_user_can_view_org_structure_page(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('org.structure'))
            ->assertOk()
            ->assertSee('Direktorat Utama')
            ->assertSee('Instalasi Gawat Darurat');
    }

    public function test_unauthorized_user_cannot_view_org_structure_page(): void
    {
        $guestUser = User::factory()->create();

        $this->actingAs($guestUser)
            ->get(route('org.structure'))
            ->assertForbidden();
    }

    public function test_can_move_position_to_another_unit(): void
    {
        $position = Position::where('title', 'Dokter Jaga IGD')->firstOrFail();
        $targetUnit = Unit::where('code', 'ICU')->firstOrFail();

        $this->assertNotEquals($targetUnit->id, $position->unit_id);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('movePosition', $position->id, $targetUnit->id)
            ->assertHasNoErrors();

        $this->assertEquals($targetUnit->id, $position->fresh()->unit_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'org.position_moved',
            'auditable_id' => $position->id,
        ]);
    }

    public function test_can_move_unit_to_another_department(): void
    {
        $unit = Unit::where('code', 'IGD')->firstOrFail();
        $targetDept = Department::where('code', 'KEP')->firstOrFail();

        $this->assertNotEquals($targetDept->id, $unit->department_id);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('moveUnit', $unit->id, $targetDept->id)
            ->assertHasNoErrors();

        $this->assertEquals($targetDept->id, $unit->fresh()->department_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'org.unit_moved',
            'auditable_id' => $unit->id,
        ]);
    }

    public function test_can_move_department_to_new_parent(): void
    {
        $dept = Department::where('code', 'YANMED')->firstOrFail();
        $targetParent = Department::where('code', 'KEU')->firstOrFail();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('moveDepartment', $dept->id, $targetParent->id)
            ->assertHasNoErrors();

        $this->assertEquals($targetParent->id, $dept->fresh()->parent_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'org.department_moved',
            'auditable_id' => $dept->id,
        ]);
    }

    public function test_cannot_move_department_into_itself_or_descendant(): void
    {
        $dir = Department::where('code', 'DIR')->firstOrFail();
        $child = Department::where('code', 'YANMED')->firstOrFail();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('moveDepartment', $dir->id, $child->id);

        $this->assertNull($dir->fresh()->parent_id);
    }

    public function test_unauthorized_user_cannot_reorganize_structure(): void
    {
        $position = Position::where('title', 'Dokter Jaga IGD')->firstOrFail();
        $targetUnit = Unit::where('code', 'ICU')->firstOrFail();
        $oldUnitId = $position->unit_id;

        Livewire::actingAs($this->employeeUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('movePosition', $position->id, $targetUnit->id)
            ->assertForbidden();

        $this->assertEquals($oldUnitId, $position->fresh()->unit_id);
    }

    // ==========================================
    // CRUD TESTS FOR DEPARTMENT, UNIT, POSITION
    // ==========================================

    public function test_can_create_edit_and_delete_department(): void
    {
        // 1. Create
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreateDepartment')
            ->set('deptCode', 'DEP-NEW')
            ->set('deptName', 'Departemen Baru')
            ->set('deptIsActive', true)
            ->call('saveDepartment')
            ->assertHasNoErrors();

        $dept = Department::where('code', 'DEP-NEW')->firstOrFail();
        $this->assertEquals('Departemen Baru', $dept->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.created', 'auditable_id' => $dept->id]);

        // 2. Edit
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('editDepartment', $dept->id)
            ->set('deptName', 'Departemen Baru Diubah')
            ->call('saveDepartment')
            ->assertHasNoErrors();

        $this->assertEquals('Departemen Baru Diubah', $dept->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.updated', 'auditable_id' => $dept->id]);

        // 3. Delete
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('confirmDelete', 'department', $dept->id, $dept->name)
            ->call('executeDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'department.deleted', 'auditable_id' => $dept->id]);
    }

    public function test_cannot_delete_department_with_associated_units(): void
    {
        $dept = Department::where('code', 'YANMED')->firstOrFail();
        $this->assertTrue($dept->units()->exists());

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('confirmDelete', 'department', $dept->id, $dept->name)
            ->call('executeDelete');

        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_can_create_edit_and_delete_unit(): void
    {
        $dept = Department::where('code', 'YANMED')->firstOrFail();

        // 1. Create
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreateUnit', $dept->id)
            ->set('unitCode', 'UNT-NEW')
            ->set('unitName', 'Unit Baru')
            ->set('unitDepartmentId', $dept->id)
            ->set('unitIsActive', true)
            ->call('saveUnit')
            ->assertHasNoErrors();

        $unit = Unit::where('code', 'UNT-NEW')->firstOrFail();
        $this->assertEquals('Unit Baru', $unit->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'unit.created', 'auditable_id' => $unit->id]);

        // 2. Edit
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('editUnit', $unit->id)
            ->set('unitName', 'Unit Baru Diubah')
            ->call('saveUnit')
            ->assertHasNoErrors();

        $this->assertEquals('Unit Baru Diubah', $unit->fresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'unit.updated', 'auditable_id' => $unit->id]);

        // 3. Delete
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('confirmDelete', 'unit', $unit->id, $unit->name)
            ->call('executeDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'unit.deleted', 'auditable_id' => $unit->id]);
    }

    public function test_cannot_delete_unit_with_associated_positions(): void
    {
        $unit = Unit::where('code', 'IGD')->firstOrFail();
        $this->assertTrue($unit->positions()->exists());

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('confirmDelete', 'unit', $unit->id, $unit->name)
            ->call('executeDelete');

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    public function test_can_create_edit_and_delete_position(): void
    {
        $unit = Unit::where('code', 'IGD')->firstOrFail();

        // 1. Create
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreatePosition', $unit->id)
            ->set('positionTitle', 'Posisi Percobaan Baru')
            ->set('positionUnitId', $unit->id)
            ->set('positionGradeLevel', 'supervisor')
            ->call('savePosition')
            ->assertHasNoErrors();

        $pos = Position::where('title', 'Posisi Percobaan Baru')->firstOrFail();
        $this->assertEquals('supervisor', $pos->grade_level);
        $this->assertDatabaseHas('audit_logs', ['action' => 'position.created', 'auditable_id' => $pos->id]);

        // 2. Edit
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('editPosition', $pos->id)
            ->set('positionTitle', 'Posisi Percobaan Diedit')
            ->set('positionGradeLevel', 'manager')
            ->call('savePosition')
            ->assertHasNoErrors();

        $this->assertEquals('Posisi Percobaan Diedit', $pos->fresh()->title);
        $this->assertEquals('manager', $pos->fresh()->grade_level);
        $this->assertDatabaseHas('audit_logs', ['action' => 'position.updated', 'auditable_id' => $pos->id]);

        // 3. Delete
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('confirmDelete', 'position', $pos->id, $pos->title)
            ->call('executeDelete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('positions', ['id' => $pos->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'position.deleted', 'auditable_id' => $pos->id]);
    }

    public function test_unauthorized_user_cannot_create_or_delete_org_entities(): void
    {
        // Try creating department
        Livewire::actingAs($this->employeeUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreateDepartment')
            ->assertForbidden();

        // Try creating unit
        Livewire::actingAs($this->employeeUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreateUnit')
            ->assertForbidden();

        // Try creating position
        Livewire::actingAs($this->employeeUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreatePosition')
            ->assertForbidden();

        // Try deleting
        $pos = Position::where('title', 'Dokter Jaga IGD')->firstOrFail();
        Livewire::actingAs($this->employeeUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('confirmDelete', 'position', $pos->id, $pos->title)
            ->assertForbidden();
    }

    public function test_can_create_position_directly_under_department(): void
    {
        $dept = Department::where('code', 'DIR')->firstOrFail();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreatePosition', null, $dept->id)
            ->set('positionTitle', 'Direktur Utama Eksekutif')
            ->set('positionPlacementType', 'department')
            ->set('positionDepartmentId', $dept->id)
            ->set('positionGradeLevel', 'director')
            ->call('savePosition')
            ->assertHasNoErrors();

        $pos = Position::where('title', 'Direktur Utama Eksekutif')->firstOrFail();
        $this->assertEquals($dept->id, $pos->department_id);
        $this->assertNull($pos->unit_id);
        $this->assertEquals('director', $pos->grade_level);
        $this->assertTrue($dept->positions->contains('id', $pos->id));
    }

    public function test_can_move_position_to_department_and_back_to_unit(): void
    {
        $pos = Position::where('title', 'Dokter Jaga IGD')->firstOrFail();
        $dept = Department::where('code', 'YANMED')->firstOrFail();
        $unit = Unit::where('code', 'ICU')->firstOrFail();

        // 1. Move to Department
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('movePosition', $pos->id, $dept->id, 'department')
            ->assertHasNoErrors();

        $pos->refresh();
        $this->assertEquals($dept->id, $pos->department_id);
        $this->assertNull($pos->unit_id);

        // 2. Move back to Unit
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('movePosition', $pos->id, $unit->id, 'unit')
            ->assertHasNoErrors();

        $pos->refresh();
        $this->assertEquals($unit->id, $pos->unit_id);
        $this->assertNull($pos->department_id);
    }

    public function test_cannot_delete_department_with_direct_positions(): void
    {
        $dept = Department::factory()->create([
            'code' => 'TEST-DEP',
            'name' => 'Departemen Uji Coba',
        ]);

        $pos = Position::create([
            'department_id' => $dept->id,
            'title' => 'Pimpinan Dept Uji Coba',
            'grade_level' => 'manager',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('confirmDelete', 'department', $dept->id, $dept->name)
            ->call('executeDelete');

        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_can_create_position_by_selecting_master_title(): void
    {
        $dept = Department::where('code', 'YANMED')->firstOrFail();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreatePosition', null, $dept->id)
            ->set('positionSource', 'master')
            ->set('selectedMasterTitle', 'Dokter Jaga IGD')
            ->assertSet('positionTitle', 'Dokter Jaga IGD')
            ->assertSet('positionGradeLevel', 'specialist')
            ->call('savePosition')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('positions', [
            'title' => 'Dokter Jaga IGD',
            'department_id' => $dept->id,
            'grade_level' => 'specialist',
        ]);
    }

    public function test_can_assign_existing_master_unit_to_department(): void
    {
        $sourceDept = Department::where('code', 'YANMED')->firstOrFail();
        $targetDept = Department::where('code', 'KEU')->firstOrFail();

        $unit = Unit::factory()->create([
            'code' => 'KASIR-TEST',
            'name' => 'Loket Pembayaran Kasir Test',
            'department_id' => $sourceDept->id,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreateUnit', $targetDept->id)
            ->set('unitSource', 'master')
            ->set('selectedMasterUnitId', $unit->id)
            ->set('unitDepartmentId', $targetDept->id)
            ->call('saveUnit')
            ->assertHasNoErrors();

        $this->assertEquals($targetDept->id, $unit->fresh()->department_id);
    }

    public function test_validates_when_master_unit_not_selected(): void
    {
        $dept = Department::where('code', 'KEU')->firstOrFail();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreateUnit', $dept->id)
            ->set('unitSource', 'master')
            ->set('selectedMasterUnitId', null)
            ->call('saveUnit')
            ->assertHasErrors(['selectedMasterUnitId']);
    }

    public function test_validates_when_master_title_not_selected(): void
    {
        $dept = Department::where('code', 'KEU')->firstOrFail();

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('openCreatePosition', null, $dept->id)
            ->set('positionSource', 'master')
            ->set('selectedMasterTitle', '')
            ->set('positionTitle', '')
            ->call('savePosition')
            ->assertHasErrors(['selectedMasterTitle']);
    }
}

