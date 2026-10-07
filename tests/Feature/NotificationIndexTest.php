<?php

namespace Tests\Feature;

use App\Livewire\Notification\NotificationIndex;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_notification_center(): void
    {
        $user = User::factory()->create([
            'name' => 'Dr. Hasan Basri',
            'is_active' => true,
        ]);
        $user->assignRole('doctor');

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Pusat Pemberitahuan & Notifikasi');
        $response->assertSee('Total Notifikasi');
        $response->assertSee('Sistem RS Permata Bunda Aktif');
    }

    public function test_admin_sees_leave_and_expiring_doctor_credentials(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin HR',
            'is_active' => true,
        ]);
        $admin->assignRole('hr_admin');

        $leaveType = LeaveType::factory()->create();
        $employee = Employee::factory()->create(['status' => 'ACTIVE']);

        // Create pending leave request
        LeaveRequest::create([
            'uuid' => (string) Str::uuid(),
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'total_days' => 2,
            'reason' => 'Urusan keluarga',
            'status' => 'pending_tier1',
            'created_by' => $admin->id,
        ]);

        // Create doctor with expiring STR
        Doctor::create([
            'name' => 'dr. Budi Raharjo, Sp.B',
            'specialization' => 'Bedah Umum',
            'str_number' => 'STR-1234567890',
            'sip_number' => 'SIP-1234567890',
            'str_valid_until' => Carbon::now()->addDays(10)->toDateString(),
            'sip_valid_until' => Carbon::now()->addMonths(6)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Antrean Persetujuan Cuti Karyawan');
        $response->assertSee('Kepatuhan Izin Praktik Medis Dokter');
    }

    public function test_livewire_component_can_filter_and_dismiss_notifications(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super_admin');

        $leaveType = LeaveType::factory()->create();
        $employee = Employee::factory()->create(['status' => 'ACTIVE']);

        LeaveRequest::create([
            'uuid' => (string) Str::uuid(),
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan mendesak',
            'status' => 'pending_tier1',
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(NotificationIndex::class)
            ->assertSee('Antrean Persetujuan Cuti Karyawan')
            ->set('selectedCategory', 'sistem')
            ->assertDontSee('Antrean Persetujuan Cuti Karyawan')
            ->assertSee('Sistem RS Permata Bunda Aktif')
            ->set('selectedCategory', 'all')
            ->assertSee('Antrean Persetujuan Cuti Karyawan')
            ->call('dismiss', 'leave_pending_admin')
            ->assertDontSee('Antrean Persetujuan Cuti Karyawan')
            ->call('restoreDismissed')
            ->assertSee('Antrean Persetujuan Cuti Karyawan');
    }
}
