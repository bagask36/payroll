<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SidebarAndModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(TestUserSeeder::class);
        $this->seed(MasterPayrollAndSystemSeeder::class);

        $this->adminUser = User::where('email', 'admin@juwitahospital.com')->first();

        $this->staffUser = User::factory()->create([
            'email' => 'staff.test@rsjuwita.co.id',
            'is_active' => true,
        ]);
        $this->staffUser->assignRole('employee');
    }

    public function test_sidebar_contains_all_seven_menu_sections_for_admin(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Data Master');
        $response->assertSee('KEPEGAWAIAN & MEDIS');
        $response->assertSee('WAKTU & OPERASIONAL');
        $response->assertSee('KEUANGAN & DOKUMEN');
        $response->assertSee('Laporan');
        $response->assertSee('ADMINISTRASI & SISTEM');

        // Check individual items
        $response->assertSee('Unit Kerja');
        $response->assertSee('Standar Jabatan');
        $response->assertSee('Shift Kerja');
        $response->assertSee('Jenis Cuti');
        $response->assertSee('Titik Geofence');
        $response->assertSee('Periode Payroll');
        $response->assertSee('Komponen Penghasilan');
        $response->assertSee('Komponen Potongan');
        $response->assertSee('Regulasi BPJS');
        $response->assertSee('Parameter PPh 21');

        $response->assertSee('Pegawai & Staf');
        $response->assertSee('Tenaga Medis & Dokter');
        $response->assertSee('Struktur Organisasi');

        $response->assertSee('Jadwal & Shift Roster');
        $response->assertSee('Presensi');
        $response->assertSee('Pengajuan Cuti & Izin');

        $response->assertSee('Penggajian (Payroll)');
        $response->assertSee('Arsip Dokumen');

        $response->assertSee('Laporan Kepegawaian');
        $response->assertSee('Laporan Kehadiran');
        $response->assertSee('Laporan Cuti & Izin');
        $response->assertSee('Laporan Payroll');
        $response->assertSee('Laporan PPh 21');
        $response->assertSee('Laporan Dokter');

        $response->assertSee('Audit Trail');
        $response->assertSee('Pengaturan Sistem');
    }

    public function test_admin_can_access_all_new_master_and_report_routes(): void
    {
        $routes = [
            '/master/earnings',
            '/master/deductions',
            '/master/pph21',
            '/org/structure',
            '/reports/employees',
            '/reports/attendance',
            '/reports/leaves',
            '/reports/payroll',
            '/reports/pph21',
            '/reports/doctors',
            '/system/settings',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->adminUser)->get($route);
            $response->assertStatus(200, "Failed asserting that {$route} returns 200 OK");
        }
    }

    public function test_unauthorized_user_cannot_access_restricted_master_and_settings(): void
    {
        $restrictedRoutes = [
            '/master/earnings',
            '/master/deductions',
            '/master/pph21',
            '/system/settings',
        ];

        foreach ($restrictedRoutes as $route) {
            $response = $this->actingAs($this->staffUser)->get($route);
            $response->assertStatus(403);
        }
    }

    public function test_sidebar_for_employee_only_contains_self_service_menus(): void
    {
        $department = Department::firstOrCreate(['code' => 'YANMED'], ['name' => 'Pelayanan Medis', 'is_active' => true]);
        $unit = Unit::firstOrCreate(['code' => 'IGD'], ['name' => 'Instalasi Gawat Darurat', 'department_id' => $department->id, 'is_active' => true]);
        $position = \App\Models\Position::firstOrCreate(['code' => 'PERAWAT'], ['title' => 'Perawat Pelaksana', 'unit_id' => $unit->id, 'grade_level' => 'staff']);
        Employee::factory()->create([
            'user_id' => $this->staffUser->id,
            'unit_id' => $unit->id,
            'position_id' => $position->id,
            'nip' => 'PEG-TEST-001',
            'full_name' => 'Budi Staff Test',
        ]);

        $response = $this->actingAs($this->staffUser)->get('/dashboard');
        $response->assertStatus(200);

        // Self-service allowed menus
        $response->assertSee('Dashboard');
        $response->assertSee('WAKTU & OPERASIONAL');
        $response->assertSee('Jadwal & Shift Roster');
        $response->assertSee('Presensi');
        $response->assertSee('Pengajuan Cuti & Izin');
        $response->assertSee('KEUANGAN & DOKUMEN');
        $response->assertSee('Slip Gaji');
        $response->assertSee('Arsip Dokumen');

        // Admin & management menus MUST NOT BE SHOWN
        $response->assertDontSee('Data Master');
        $response->assertDontSee('KEPEGAWAIAN & MEDIS');
        $response->assertDontSee('Pegawai & Staf');
        $response->assertDontSee('Tenaga Medis & Dokter');
        $response->assertDontSee('Struktur Organisasi');
        $response->assertDontSee('Penggajian (Payroll)');
        $response->assertDontSee('Laporan Kepegawaian');
        $response->assertDontSee('Laporan Kehadiran');
        $response->assertDontSee('Laporan Cuti & Izin');
        $response->assertDontSee('Laporan Payroll');
        $response->assertDontSee('Laporan Dokter');
        $response->assertDontSee('Audit Trail');
        $response->assertDontSee('Pengaturan Sistem');
        $response->assertDontSee('ADMINISTRASI & SISTEM');
    }

    public function test_can_create_and_toggle_earning_component(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Master\EarningComponentIndex::class)
            ->set('code', 'TUNJ_INSENTIF_KHUSUS')
            ->set('name', 'Tunjangan Insentif Khusus')
            ->set('defaultAmount', 750000)
            ->set('isFixed', true)
            ->set('isTaxable', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_components', [
            'code' => 'TUNJ_INSENTIF_KHUSUS',
            'type' => 'allowance',
            'is_fixed' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Master\EarningComponentIndex::class)
            ->set('code', 'TUNJ_MAKAN_HARIAN')
            ->set('name', 'Tunjangan Uang Makan Harian')
            ->set('defaultAmount', 45000)
            ->set('isFixed', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_components', [
            'code' => 'TUNJ_MAKAN_HARIAN',
            'type' => 'allowance',
            'is_fixed' => false,
        ]);
    }

    public function test_can_create_and_toggle_deduction_component(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Master\DeductionComponentIndex::class)
            ->set('code', 'POT_SIMPANAN_SUKARELA')
            ->set('name', 'Potongan Simpanan Sukarela')
            ->set('defaultAmount', 50000)
            ->set('isFixed', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_components', [
            'code' => 'POT_SIMPANAN_SUKARELA',
            'type' => 'deduction',
        ]);
    }

    public function test_can_update_system_settings(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\System\SystemSettingIndex::class)
            ->set('hospitalName', 'RS Permata Bunda Sentosa')
            ->set('hospitalPhone', '(021) 99887766')
            ->set('lateGracePeriodMinutes', 20)
            ->set('defaultGeofenceRadius', 150)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('system_settings', [
            'key' => 'hospital_name',
            'value' => 'RS Permata Bunda Sentosa',
        ]);
    }

    public function test_sidebar_renders_proper_collapsed_classes_and_elements(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('in-data-flux-sidebar-collapsed-desktop:justify-center');
        $response->assertSee('in-data-flux-sidebar-collapsed-desktop:inset-0');
        $response->assertSee('in-data-flux-sidebar-collapsed-desktop:size-10');
        $response->assertSee('Pegawai &amp; Staf', false);
        $response->assertSee('Presensi');
        $response->assertSee('Penggajian (Payroll)');
    }
}
