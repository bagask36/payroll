<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationDropdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_authenticated_user_can_see_notification_bell_and_open_alert_center(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Juwita',
            'is_active' => true,
        ]);
        $admin->assignRole('super_admin');

        $leaveType = LeaveType::factory()->create();
        $employee = Employee::factory()->create(['status' => 'ACTIVE']);

        // Create pending leave request
        LeaveRequest::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga mendesak',
            'status' => 'pending_tier1',
            'created_by' => $employee->user_id ?? $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Pemberitahuan & Peringatan');
        $response->assertSee('Antrean Persetujuan Cuti');
    }

    public function test_employee_sees_personal_leave_and_attendance_alerts(): void
    {
        $employeeUser = User::factory()->create([
            'name' => 'Staf Perawat',
            'is_active' => true,
        ]);
        $employeeUser->assignRole('employee');
        $employee = Employee::factory()->create([
            'user_id' => $employeeUser->id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($employeeUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Pemberitahuan & Peringatan');
        $response->assertSee('Presensi Hari Ini Belum Masuk');
    }
}
