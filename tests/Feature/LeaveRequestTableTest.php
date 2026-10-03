<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\LeaveRequestTable;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class LeaveRequestTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            LeaveSeeder::class,
        ]);
    }

    public function test_powergrid_leave_request_table_renders_data(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('employee');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $annualType = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annualType->id,
            'start_date' => Carbon::today()->addDays(5),
            'end_date' => Carbon::today()->addDays(6),
            'total_days' => 2.0,
            'reason' => 'Acara keluarga tahunan',
            'status' => 'pending_tier1',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(LeaveRequestTable::class)
            ->assertStatus(200)
            ->assertSee('Acara keluarga tahunan')
            ->assertSee('2 Hari')
            ->assertSee('Menunggu Supervisor (T1)');
    }
}
