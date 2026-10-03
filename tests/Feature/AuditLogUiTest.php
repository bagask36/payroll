<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Audit\AuditLogIndex;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unauthorized_user_is_forbidden_from_viewing_audit_trail(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employee');

        $this->actingAs($employeeUser);

        $this->get(route('audit-logs.index'))
            ->assertStatus(403);
    }

    public function test_super_admin_can_view_audit_logs_and_inspect_payload(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $log = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'payroll.calculated',
            'auditable_type' => 'App\Models\PayrollRun',
            'auditable_id' => 42,
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'calculated', 'total_net_pay' => 54000000],
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Suite',
        ]);

        $this->actingAs($admin);

        // HTTP access to audit trail page
        $this->get(route('audit-logs.index'))
            ->assertStatus(200)
            ->assertSee('Audit Trail')
            ->assertSee('payroll.calculated');

        // Modal payload inspection
        Livewire::test(AuditLogIndex::class)
            ->assertStatus(200)
            ->assertSee('Audit Trail')
            ->dispatch('open-audit-payload', logId: $log->id)
            ->assertSet('showDetailModal', true)
            ->assertSee('Detail Payload Audit Log')
            ->assertSee('54000000');
    }
}
