<?php

namespace App\Livewire;

use App\Services\Reporting\HospitalDashboardService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard - RS Juwita')]
class Dashboard extends Component
{
    public array $staffing = [];

    public array $attendance = [];

    public ?array $payroll = null;

    public array $doctorAlerts = [];

    public function mount(HospitalDashboardService $dashboardService): void
    {
        $this->loadDashboardData($dashboardService);
    }

    public function refreshData(HospitalDashboardService $dashboardService): void
    {
        $this->loadDashboardData($dashboardService);
    }

    protected function loadDashboardData(HospitalDashboardService $dashboardService): void
    {
        $this->staffing = $dashboardService->getStaffingMetrics();
        $this->attendance = $dashboardService->getAttendanceMetricsForDate();
        $this->payroll = $dashboardService->getPayrollSummary();
        $this->doctorAlerts = $dashboardService->getDoctorCredentialsAlerts(expiringDays: 60);
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
