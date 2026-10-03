<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_dashboard_page(): void
    {
        $user = User::factory()->create([
            'name' => 'dr. Juwita Sp.A',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Operasional');
        $response->assertSee('dr. Juwita Sp.A');
    }

    public function test_dashboard_renders_metrics_and_alerts(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        Employee::factory()->count(5)->create(['status' => 'ACTIVE']);

        // Doctor with expiring STR
        Doctor::factory()->create([
            'name' => 'dr. Hendra Sp.PD',
            'str_valid_until' => Carbon::today()->addDays(10)->toDateString(),
            'sip_valid_until' => Carbon::today()->addYears(2)->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSee('Total Karyawan Aktif')
            ->assertSee('Distribusi Ketenagaan Rumah Sakit')
            ->assertSee('dr. Hendra Sp.PD')
            ->assertSee('Peringatan Kepatuhan Izin Praktik Medis')
            ->call('refreshData')
            ->assertStatus(200);
    }
}
