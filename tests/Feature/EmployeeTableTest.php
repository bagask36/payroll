<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\EmployeeTable;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_powergrid_employee_table_renders_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $unit = Unit::factory()->create(['name' => 'Instalasi Rawat Inap']);
        $pos = Position::factory()->create(['title' => 'Kepala Ruangan']);

        $emp = Employee::factory()->create([
            'full_name' => 'Ns. Budi Santoso, S.Kep',
            'nip' => 'PEG-2026999',
            'unit_id' => $unit->id,
            'position_id' => $pos->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        Livewire::test(EmployeeTable::class)
            ->assertStatus(200)
            ->assertSee('Ns. Budi Santoso, S.Kep')
            ->assertSee('PEG-2026999')
            ->assertSee('Instalasi Rawat Inap');
    }
}
