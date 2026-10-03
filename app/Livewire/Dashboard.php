<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use App\Models\ScheduleAssignment;
use App\Services\Reporting\HospitalDashboardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard - RS Juwita')]
class Dashboard extends Component
{
    // Hospital / Admin metrics
    public array $staffing = [];

    public array $attendance = [];

    public ?array $payroll = null;

    public array $doctorAlerts = [];

    public int $pendingLeavesCount = 0;

    // Role View Indicator
    public string $userRole = 'management';

    public function getGreetingProperty(): string
    {
        $hour = (int) Carbon::now()->format('H');
        if ($hour < 11) {
            return 'Selamat Pagi';
        } elseif ($hour < 15) {
            return 'Selamat Siang';
        } elseif ($hour < 18) {
            return 'Selamat Sore';
        }
        return 'Selamat Malam';
    }

    // Employee / Doctor Self-Service Metrics
    public ?array $myShiftToday = null;

    public ?array $myShiftTomorrow = null;

    public ?array $myAttendanceToday = null;

    public ?array $myLeaveQuota = null;

    public ?array $myLatestPayslip = null;

    public array $myRecentLeaves = [];

    public ?array $myDoctorCredentials = null;

    // Supervisor Team Metrics
    public ?array $teamMetrics = null;

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
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager')) {
            $this->userRole = 'management';
            $this->staffing = $dashboardService->getStaffingMetrics();
            $this->attendance = $dashboardService->getAttendanceMetricsForDate();
            $this->payroll = $dashboardService->getPayrollSummary();
            $this->doctorAlerts = $dashboardService->getDoctorCredentialsAlerts(expiringDays: 60);
            $this->pendingLeavesCount = LeaveRequest::whereIn('status', ['pending_tier1', 'pending_tier2'])->count();
        } elseif ($user->hasRole('supervisor')) {
            $this->userRole = 'supervisor';
            $this->loadSupervisorData($user);
            $this->loadEmployeeData($user);
        } elseif ($user->hasRole('payroll') || $user->hasRole('finance')) {
            $this->userRole = 'payroll_finance';
            $this->payroll = $dashboardService->getPayrollSummary();
            $this->staffing = $dashboardService->getStaffingMetrics();
            $this->loadEmployeeData($user);
        } elseif ($user->hasRole('doctor')) {
            $this->userRole = 'doctor';
            $this->loadDoctorData($user);
            $this->loadEmployeeData($user);
        } elseif ($user->hasRole('employee')) {
            $this->userRole = 'employee';
            $this->loadEmployeeData($user);
        } else {
            // Fallback for default test users or users without explicit roles
            $this->userRole = 'management';
            $this->staffing = $dashboardService->getStaffingMetrics();
            $this->attendance = $dashboardService->getAttendanceMetricsForDate();
            $this->payroll = $dashboardService->getPayrollSummary();
            $this->doctorAlerts = $dashboardService->getDoctorCredentialsAlerts(expiringDays: 60);
            $this->pendingLeavesCount = LeaveRequest::whereIn('status', ['pending_tier1', 'pending_tier2'])->count();
        }
    }

    protected function loadEmployeeData($user): void
    {
        $employee = $user->employee;
        if (! $employee) {
            return;
        }

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // 1. Shift Today & Tomorrow
        $shiftToday = ScheduleAssignment::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('schedule_date', $today)
            ->first();

        $this->myShiftToday = $shiftToday ? [
            'shift_name' => $shiftToday->shift?->name ?? 'Jadwal Reguler',
            'start_time' => $shiftToday->shift?->start_time ? Carbon::parse($shiftToday->shift->start_time)->format('H:i') : '-',
            'end_time' => $shiftToday->shift?->end_time ? Carbon::parse($shiftToday->shift->end_time)->format('H:i') : '-',
            'status' => $shiftToday->status,
        ] : null;

        $shiftTomorrow = ScheduleAssignment::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('schedule_date', $tomorrow)
            ->first();

        $this->myShiftTomorrow = $shiftTomorrow ? [
            'shift_name' => $shiftTomorrow->shift?->name ?? 'Jadwal Reguler',
            'start_time' => $shiftTomorrow->shift?->start_time ? Carbon::parse($shiftTomorrow->shift->start_time)->format('H:i') : '-',
            'end_time' => $shiftTomorrow->shift?->end_time ? Carbon::parse($shiftTomorrow->shift->end_time)->format('H:i') : '-',
        ] : null;

        // 2. Attendance Today
        $attToday = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        $this->myAttendanceToday = [
            'has_checked_in' => $attToday && $attToday->clock_in !== null,
            'clock_in' => $attToday?->clock_in ? Carbon::parse($attToday->clock_in)->format('H:i') : null,
            'clock_out' => $attToday?->clock_out ? Carbon::parse($attToday->clock_out)->format('H:i') : null,
            'status' => $attToday?->status ?? 'belum_hadir',
            'late_minutes' => $attToday?->late_duration_minutes ?? 0,
        ];

        // 3. Leave Quota Balance
        $currentYear = Carbon::now()->year;
        $balances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->with('leaveType')
            ->get();

        $this->myLeaveQuota = [
            'remaining_days' => $balances->sum('remaining_days'),
            'used_days' => $balances->sum('used_days'),
            'allocated_days' => $balances->sum('assigned_days'),
        ];

        // 4. Latest Payslip Snapshot
        $latestItem = PayrollItem::where('employee_id', $employee->id)
            ->with('run.period')
            ->latest('id')
            ->first();

        $this->myLatestPayslip = $latestItem ? [
            'period_name' => $latestItem->run?->period?->name ?? 'Periode Terakhir',
            'net_salary' => $latestItem->net_salary,
            'status' => $latestItem->run?->status ?? 'finalized',
            'payment_status' => $latestItem->payment_status,
        ] : null;

        // 5. Recent Leaves
        $this->myRecentLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->with('leaveType')
            ->latest('id')
            ->take(3)
            ->get()
            ->map(fn ($r) => [
                'type' => $r->leaveType?->name ?? 'Cuti',
                'start_date' => $r->start_date->format('d/m/Y'),
                'end_date' => $r->end_date->format('d/m/Y'),
                'total_days' => $r->total_days,
                'status' => $r->status,
                'reason' => $r->reason,
            ])
            ->toArray();
    }

    protected function loadDoctorData($user): void
    {
        $doctor = $user->doctor;
        if (! $doctor) {
            return;
        }

        $this->myDoctorCredentials = [
            'name' => $doctor->name,
            'specialization' => $doctor->specialization,
            'str_number' => $doctor->str_number,
            'str_valid_until' => $doctor->str_valid_until?->format('d/m/Y'),
            'is_str_expired' => $doctor->isStrExpired(),
            'is_str_expiring_soon' => $doctor->isExpiringSoon(60),
            'sip_number' => $doctor->sip_number,
            'sip_valid_until' => $doctor->sip_valid_until?->format('d/m/Y'),
            'is_sip_expired' => $doctor->isSipExpired(),
        ];
    }

    protected function loadSupervisorData($user): void
    {
        $unit = $user->employee?->unit;
        if (! $unit) {
            return;
        }

        $unitEmployeeIds = Employee::where('unit_id', $unit->id)->pluck('id');
        $today = Carbon::today();

        $totalTeam = $unitEmployeeIds->count();
        $presentToday = Attendance::whereIn('employee_id', $unitEmployeeIds)
            ->whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->count();

        $pendingApprovals = LeaveRequest::where('status', 'pending_tier1')
            ->whereIn('employee_id', $unitEmployeeIds)
            ->count();

        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereIn('employee_id', $unitEmployeeIds)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $this->teamMetrics = [
            'unit_name' => $unit->name,
            'total_team' => $totalTeam,
            'present_today' => $presentToday,
            'pending_approvals' => $pendingApprovals,
            'on_leave_today' => $onLeaveToday,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
