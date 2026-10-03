<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Tables\AttendanceTable;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_powergrid_attendance_table_renders_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $emp = Employee::factory()->create(['full_name' => 'Budi Santoso, A.Md.Kep']);

        Attendance::create([
            'employee_id' => $emp->id,
            'date' => Carbon::parse('2026-10-03'),
            'clock_in' => Carbon::parse('2026-10-03 06:55:00'),
            'clock_out' => Carbon::parse('2026-10-03 14:10:00'),
            'work_duration_minutes' => 435,
            'status' => 'present',
        ]);

        $this->actingAs($admin);

        Livewire::test(AttendanceTable::class)
            ->assertStatus(200)
            ->assertSee('Budi Santoso, A.Md.Kep')
            ->assertSee('06:55')
            ->assertSee('14:10')
            ->assertSee('Tepat Waktu');
    }
}
