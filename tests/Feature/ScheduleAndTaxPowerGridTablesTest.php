<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Master\BpjsIndex;
use App\Livewire\Master\Pph21ParameterIndex;
use App\Livewire\Schedule\ScheduleRoster;
use App\Livewire\Tables\BpjsRuleTable;
use App\Livewire\Tables\Pph21PtkpTable;
use App\Livewire\Tables\Pph21TarifTable;
use App\Livewire\Tables\ScheduleTable;
use App\Models\BpjsRule;
use App\Models\Pph21Parameter;
use App\Models\ScheduleAssignment;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\MasterPayrollAndSystemSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleAndTaxPowerGridTablesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(ScheduleSeeder::class);
        $this->seed(MasterPayrollAndSystemSeeder::class);

        $this->admin = User::where('email', 'admin@juwitahospital.com')->firstOrFail();
        $this->actingAs($this->admin);
    }

    public function test_schedule_table_renders(): void
    {
        $shift = Shift::first();
        ScheduleAssignment::create([
            'shift_id' => $shift->id,
            'schedule_date' => now()->toDateString(),
            'status' => 'scheduled',
            'notes' => 'Tugas IGD Shift Pagi',
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(ScheduleTable::class)
            ->assertStatus(200)
            ->assertSee('Tugas IGD Shift Pagi');
    }

    public function test_schedule_roster_page_renders_with_powergrid(): void
    {
        $this->get(route('schedules.index'))
            ->assertStatus(200)
            ->assertSeeLivewire(ScheduleTable::class);
    }

    public function test_bpjs_rule_table_renders(): void
    {
        BpjsRule::firstOrCreate(
            ['program_name' => 'kesehatan'],
            [
                'employee_rate' => 0.01,
                'employer_rate' => 0.04,
                'wage_ceiling' => 12000000,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ]
        );

        Livewire::test(BpjsRuleTable::class)
            ->assertStatus(200)
            ->assertSee('BPJS KESEHATAN')
            ->assertSee('1.00%')
            ->assertSee('4.00%');
    }

    public function test_bpjs_index_page_renders_with_powergrid(): void
    {
        $this->get(route('master.bpjs'))
            ->assertStatus(200)
            ->assertSeeLivewire(BpjsRuleTable::class);
    }

    public function test_pph21_ptkp_table_renders(): void
    {
        Pph21Parameter::firstOrCreate(
            ['code' => 'PTKP_TK0'],
            [
                'category' => 'ptkp',
                'name' => 'Tidak Kawin Tanpa Tanggungan (TK/0)',
                'amount' => 54000000,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ]
        );

        Livewire::test(Pph21PtkpTable::class)
            ->assertStatus(200)
            ->assertSee('PTKP_TK0')
            ->assertSee('54.000.000');
    }

    public function test_pph21_tarif_table_renders(): void
    {
        Pph21Parameter::firstOrCreate(
            ['code' => 'LAPISAN_1'],
            [
                'category' => 'tarif_pasal_17',
                'name' => 'Lapisan 1 (s/d 60 Juta)',
                'rate' => 0.05,
                'threshold_min' => 0,
                'threshold_max' => 60000000,
                'effective_from' => '2024-01-01',
                'is_active' => true,
            ]
        );

        Livewire::test(Pph21TarifTable::class)
            ->assertStatus(200)
            ->assertSee('LAPISAN_1')
            ->assertSee('5.0%');
    }

    public function test_pph21_parameter_page_renders_with_powergrid(): void
    {
        $this->get(route('master.pph21'))
            ->assertStatus(200)
            ->assertSeeLivewire(Pph21PtkpTable::class);
    }
}
