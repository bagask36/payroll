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
        // User with no permissions
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

        // Trying to make child the parent of its own parent (DIR -> YANMED)
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Organization\OrgStructureIndex::class)
            ->call('moveDepartment', $dir->id, $child->id);

        // DIR parent_id must not change
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
}
