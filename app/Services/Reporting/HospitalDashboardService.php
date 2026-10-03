<?php

namespace App\Services\Reporting;

use App\Models\Attendance;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\PayrollRun;
use App\Models\ScheduleAssignment;
use Illuminate\Support\Carbon;

class HospitalDashboardService
{
    /**
     * Get hospital staffing headcount and contractual metrics.
     *
     * @return array<string, mixed>
     */
    public function getStaffingMetrics(): array
    {
        $activeEmployees = Employee::active()->count();
        $activeDoctors = Doctor::active()->count();

        $contractCounts = EmployeeEmployment::where('is_active', true)
            ->selectRaw('contract_type, count(*) as count')
            ->groupBy('contract_type')
            ->pluck('count', 'contract_type')
            ->toArray();

        return [
            'total_active_employees' => $activeEmployees,
            'total_active_doctors' => $activeDoctors,
            'total_workforce' => $activeEmployees + $activeDoctors,
            'contract_breakdown' => $contractCounts,
        ];
    }

    /**
     * Get operational attendance statistics for a given date.
     *
     * @return array<string, mixed>
     */
    public function getAttendanceMetricsForDate(string $date): array
    {
        $dateString = Carbon::parse($date)->toDateString();

        $totalScheduled = ScheduleAssignment::whereDate('schedule_date', $dateString)
            ->where('status', '!=', 'cancelled')
            ->count();

        $attendances = Attendance::whereDate('date', $dateString)->get();

        $presentCount = $attendances->where('status', 'present')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $totalMinutesLate = (int) $attendances->sum('late_duration_minutes');

        return [
            'date' => $dateString,
            'total_scheduled' => $totalScheduled,
            'total_checked_in' => $attendances->count(),
            'present_count' => $presentCount,
            'late_count' => $lateCount,
            'total_late_minutes' => $totalMinutesLate,
        ];
    }

    /**
     * Get monthly payroll and statutory compensation summary.
     *
     * @return array<string, mixed>
     */
    public function getPayrollSummary(int $runId): array
    {
        $run = PayrollRun::with('items')->findOrFail($runId);

        $totalGross = (float) $run->total_gross_pay;
        $totalNet = (float) $run->total_net_pay;
        $totalDeductions = (float) $run->total_deductions;

        $totalBpjsKes = (float) $run->items->sum('bpjs_kes_employee') + (float) $run->items->sum('bpjs_kes_employer');
        $totalBpjsTk = (float) $run->items->sum('bpjs_tk_employee') + (float) $run->items->sum('bpjs_tk_employer');

        return [
            'run_id' => $run->id,
            'run_uuid' => $run->uuid,
            'status' => $run->status,
            'total_payslips' => $run->items->count(),
            'total_gross_pay' => $totalGross,
            'total_net_pay' => $totalNet,
            'total_deductions' => $totalDeductions,
            'total_bpjs_kesehatan' => round($totalBpjsKes, 2),
            'total_bpjs_ketenagakerjaan' => round($totalBpjsTk, 2),
        ];
    }

    /**
     * Identify physicians with licensing credentials expiring within specified days.
     *
     * @return list<array<string, mixed>>
     */
    public function getDoctorCredentialsAlerts(int $expiringDays = 60): array
    {
        $threshold = Carbon::today()->addDays($expiringDays);

        $doctors = Doctor::active()
            ->where(function ($q) use ($threshold) {
                $q->whereBetween('str_valid_until', [Carbon::today(), $threshold])
                    ->orWhereBetween('sip_valid_until', [Carbon::today(), $threshold]);
            })
            ->get();

        return $doctors->map(fn (Doctor $doc) => [
            'doctor_id' => $doc->id,
            'name' => $doc->name,
            'specialization' => $doc->specialization,
            'str_number' => $doc->str_number,
            'str_valid_until' => $doc->str_valid_until->toDateString(),
            'sip_number' => $doc->sip_number,
            'sip_valid_until' => $doc->sip_valid_until->toDateString(),
        ])->values()->all();
    }
}
