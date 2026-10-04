<?php

namespace App\Services\Reporting;

use App\Models\Attendance;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeWarningLetter;
use App\Models\LeaveBalance;
use App\Models\PayrollRun;
use App\Models\ScheduleAssignment;
use App\Models\Unit;
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
    public function getAttendanceMetricsForDate(?string $date = null): array
    {
        $dateString = Carbon::parse($date ?? Carbon::today()->toDateString())->toDateString();

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
     * @return array<string, mixed>|null
     */
    public function getPayrollSummary(?int $runId = null): ?array
    {
        $run = $runId ? PayrollRun::with('items')->find($runId) : PayrollRun::with('items')->latest('id')->first();

        if (! $run) {
            return null;
        }

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

    /**
     * Get comprehensive HR demographics (age, gender, education, tenure) and operational KPIs.
     *
     * @return array<string, mixed>
     */
    public function getHrDemographicsAndKpis(): array
    {
        $employees = Employee::active()->with(['unit', 'currentEmployment'])->get();
        $totalEmployees = $employees->count();

        // 1. Age Distribution & Average
        $ageBuckets = [
            '< 25' => 0,
            '25 - 35' => 0,
            '36 - 45' => 0,
            '46 - 55' => 0,
            '> 55' => 0,
        ];
        $totalAge = 0;
        $validAges = 0;

        foreach ($employees as $emp) {
            if (! $emp->birth_date) {
                continue;
            }
            $age = (int) Carbon::parse($emp->birth_date)->age;
            $totalAge += $age;
            $validAges++;

            if ($age < 25) {
                $ageBuckets['< 25']++;
            } elseif ($age <= 35) {
                $ageBuckets['25 - 35']++;
            } elseif ($age <= 45) {
                $ageBuckets['36 - 45']++;
            } elseif ($age <= 55) {
                $ageBuckets['46 - 55']++;
            } else {
                $ageBuckets['> 55']++;
            }
        }

        $averageAge = $validAges > 0 ? round($totalAge / $validAges, 1) : 0.0;
        $ageDistribution = [];
        foreach ($ageBuckets as $label => $count) {
            $ageDistribution[] = [
                'range' => $label,
                'count' => $count,
                'percentage' => $validAges > 0 ? round(($count / $validAges) * 100, 1) : 0.0,
            ];
        }

        // 2. Gender Distribution
        $maleCount = 0;
        $femaleCount = 0;

        foreach ($employees as $emp) {
            $g = strtolower((string) $emp->gender);
            if (in_array($g, ['male', 'l', 'laki-laki', 'pria'], true)) {
                $maleCount++;
            } else {
                $femaleCount++;
            }
        }

        $malePct = $totalEmployees > 0 ? round(($maleCount / $totalEmployees) * 100, 1) : 0.0;
        $femalePct = $totalEmployees > 0 ? round(($femaleCount / $totalEmployees) * 100, 1) : 0.0;

        // 3. Education Distribution
        $eduCounts = [];
        foreach ($employees as $emp) {
            $edu = trim((string) ($emp->education_level ?: 'Lainnya'));
            $eduCounts[$edu] = ($eduCounts[$edu] ?? 0) + 1;
        }
        arsort($eduCounts);

        $educationDistribution = [];
        foreach ($eduCounts as $level => $count) {
            $educationDistribution[] = [
                'level' => $level,
                'count' => $count,
                'percentage' => $totalEmployees > 0 ? round(($count / $totalEmployees) * 100, 1) : 0.0,
            ];
        }

        // 4. Tenure / Masa Kerja Distribution
        $tenureBuckets = [
            '< 1 Tahun' => 0,
            '1 - 3 Tahun' => 0,
            '3 - 5 Tahun' => 0,
            '> 5 Tahun' => 0,
        ];
        $totalTenureMonths = 0;
        $tenureCount = 0;
        $now = Carbon::now();

        foreach ($employees as $emp) {
            $startDate = $emp->currentEmployment?->start_date ? Carbon::parse($emp->currentEmployment->start_date) : null;
            if (! $startDate) {
                $tenureBuckets['< 1 Tahun']++;
                continue;
            }
            $months = max(0, $startDate->diffInMonths($now));
            $totalTenureMonths += $months;
            $tenureCount++;

            $years = $months / 12;
            if ($years < 1) {
                $tenureBuckets['< 1 Tahun']++;
            } elseif ($years <= 3) {
                $tenureBuckets['1 - 3 Tahun']++;
            } elseif ($years <= 5) {
                $tenureBuckets['3 - 5 Tahun']++;
            } else {
                $tenureBuckets['> 5 Tahun']++;
            }
        }

        $avgTenureYears = $tenureCount > 0 ? round(($totalTenureMonths / $tenureCount) / 12, 1) : 0.0;
        $tenureDistribution = [];
        foreach ($tenureBuckets as $label => $count) {
            $tenureDistribution[] = [
                'range' => $label,
                'count' => $count,
                'percentage' => $totalEmployees > 0 ? round(($count / $totalEmployees) * 100, 1) : 0.0,
            ];
        }

        // 5. Unit / Departemen Distribution
        $unitCounts = [];
        foreach ($employees as $emp) {
            $unitName = $emp->unit?->name ?? 'Tanpa Unit';
            $unitCounts[$unitName] = ($unitCounts[$unitName] ?? 0) + 1;
        }
        arsort($unitCounts);
        $topUnits = [];
        foreach (array_slice($unitCounts, 0, 5, true) as $uName => $cnt) {
            $topUnits[] = [
                'name' => $uName,
                'count' => $cnt,
                'percentage' => $totalEmployees > 0 ? round(($cnt / $totalEmployees) * 100, 1) : 0.0,
            ];
        }

        // 6. Operational & Disciplinary KPIs
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $scheduledMonth = ScheduleAssignment::whereBetween('schedule_date', [$startOfMonth, $endOfMonth])
            ->where('status', '!=', 'cancelled')
            ->count();

        $attendancesMonth = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])->get();
        $checkedInMonth = $attendancesMonth->count();
        $presentMonth = $attendancesMonth->where('status', 'present')->count();

        $monthlyAttendanceRate = $scheduledMonth > 0 ? round(($checkedInMonth / $scheduledMonth) * 100, 1) : 100.0;
        $monthlyPunctualityRate = $checkedInMonth > 0 ? round(($presentMonth / $checkedInMonth) * 100, 1) : 100.0;

        $activeSpCount = EmployeeWarningLetter::active()->distinct('employee_id')->count('employee_id');
        $disciplineIndex = $totalEmployees > 0 ? round((($totalEmployees - $activeSpCount) / $totalEmployees) * 100, 1) : 100.0;

        $avgLeaveBalance = (float) LeaveBalance::where('year', Carbon::now()->year)->avg('remaining_days');

        return [
            'total_active_employees' => $totalEmployees,
            'average_age' => $averageAge,
            'age_distribution' => $ageDistribution,
            'gender' => [
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'male_percentage' => $malePct,
                'female_percentage' => $femalePct,
            ],
            'education_distribution' => $educationDistribution,
            'tenure' => [
                'average_years' => $avgTenureYears,
                'distribution' => $tenureDistribution,
            ],
            'unit_distribution' => $topUnits,
            'kpis' => [
                'attendance_rate' => min(100.0, $monthlyAttendanceRate),
                'punctuality_rate' => min(100.0, $monthlyPunctualityRate),
                'discipline_index' => $disciplineIndex,
                'active_warning_letters' => $activeSpCount,
                'avg_leave_balance' => round($avgLeaveBalance, 1),
            ],
        ];
    }
}

