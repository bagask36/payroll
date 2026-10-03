<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\AuditLogTable;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_powergrid_audit_log_table_renders_data(): void
    {
        $admin = User::factory()->create(['name' => 'Bagas Admin']);
        $admin->assignRole('super_admin');

        $log = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'payroll.calculated',
            'auditable_type' => 'App\Models\PayrollRun',
            'auditable_id' => 42,
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'calculated'],
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Suite',
        ]);

        $this->actingAs($admin);

        Livewire::test(AuditLogTable::class)
            ->assertStatus(200)
            ->assertSee('payroll.calculated')
            ->assertSee('192.168.1.100')
            ->assertSee('Bagas Admin');
    }

    public function test_powergrid_audit_log_table_filters_by_search(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'attendance.clock_in',
            'ip_address' => '10.0.0.1',
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'leave.approved',
            'ip_address' => '10.0.0.2',
        ]);

        $this->actingAs($admin);

        Livewire::test(AuditLogTable::class)
            ->set('search', 'attendance')
            ->assertSee('attendance.clock_in')
            ->assertDontSee('leave.approved');
    }
}
