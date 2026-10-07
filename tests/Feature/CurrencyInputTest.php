<?php

namespace Tests\Feature;

use App\Livewire\Master\DeductionComponentIndex;
use App\Livewire\Master\EarningComponentIndex;
use App\Livewire\Master\MasterIndex;
use App\Livewire\Master\PositionIndex;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyInputTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    public function test_flux_input_currency_component_renders_correctly(): void
    {
        View::share('errors', new ViewErrorBag);

        $html = Blade::render('<flux:input.currency label="Gaji Pokok" wire:model="salary" placeholder="Rp. 0" />');

        $this->assertStringContainsString('Gaji Pokok', $html);
        $this->assertStringContainsString('currencyInput', $html);
        $this->assertStringContainsString('data-flux-input', $html);
        $this->assertStringContainsString('inputmode="numeric"', $html);
        $this->assertStringContainsString('placeholder="Rp. 0"', $html);
    }

    public function test_x_input_currency_alias_renders_correctly(): void
    {
        View::share('errors', new ViewErrorBag);

        $html = Blade::render('<x-input-currency label="Tunjangan" wire:model="allowance" />');

        $this->assertStringContainsString('Tunjangan', $html);
        $this->assertStringContainsString('currencyInput', $html);
    }

    public function test_position_index_accepts_clean_and_formatted_currency_input(): void
    {
        $unit = Unit::factory()->create(['name' => 'Instalasi Farmasi']);

        // Test with raw numeric string
        Livewire::actingAs($this->admin)
            ->test(PositionIndex::class)
            ->set('positionTitle', 'Apoteker Penanggung Jawab')
            ->set('positionUnitId', $unit->id)
            ->set('positionDefaultBasicSalary', '6500000')
            ->set('positionDefaultPositionalAllowance', '1500000')
            ->call('savePosition')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('positions', [
            'title' => 'Apoteker Penanggung Jawab',
            'default_basic_salary' => 6500000.00,
            'default_positional_allowance' => 1500000.00,
        ]);

        // Test with formatted currency string "Rp. 7.500.000"
        Livewire::actingAs($this->admin)
            ->test(PositionIndex::class)
            ->set('positionTitle', 'Kepala Farmasi')
            ->set('positionUnitId', $unit->id)
            ->set('positionDefaultBasicSalary', 'Rp. 7.500.000')
            ->set('positionDefaultPositionalAllowance', 'Rp. 2.000.000')
            ->call('savePosition')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('positions', [
            'title' => 'Kepala Farmasi',
            'default_basic_salary' => 7500000.00,
            'default_positional_allowance' => 2000000.00,
        ]);
    }

    public function test_deduction_component_accepts_clean_and_formatted_currency(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DeductionComponentIndex::class)
            ->set('code', 'POT_SIMPANAN')
            ->set('name', 'Potongan Simpanan Wajib')
            ->set('defaultAmount', 'Rp. 25.000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_components', [
            'code' => 'POT_SIMPANAN',
            'default_amount' => 25000.00,
            'type' => 'deduction',
        ]);
    }

    public function test_earning_component_accepts_clean_and_formatted_currency(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EarningComponentIndex::class)
            ->set('code', 'TUNJ_MAKAN')
            ->set('name', 'Tunjangan Uang Makan')
            ->set('defaultAmount', 'Rp. 50.000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_components', [
            'code' => 'TUNJ_MAKAN',
            'default_amount' => 50000.00,
            'type' => 'allowance',
        ]);
    }

    public function test_master_index_accepts_clean_and_formatted_currency(): void
    {
        $unit = Unit::factory()->create(['name' => 'Instalasi Rawat Inap']);

        Livewire::actingAs($this->admin)
            ->test(MasterIndex::class)
            ->set('positionTitle', 'Kepala Ruang Rawat Inap')
            ->set('positionUnitId', $unit->id)
            ->set('positionDefaultBasicSalary', 'Rp. 8.500.000')
            ->set('positionDefaultPositionalAllowance', 'Rp. 2.500.000')
            ->call('savePosition')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('positions', [
            'title' => 'Kepala Ruang Rawat Inap',
            'default_basic_salary' => 8500000.00,
            'default_positional_allowance' => 2500000.00,
        ]);
    }
}

