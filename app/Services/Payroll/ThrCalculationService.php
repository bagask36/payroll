<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class ThrCalculationService
{
    public function __construct(
        protected ?Pph21CalculationService $pph21Service = null
    ) {
        $this->pph21Service = $pph21Service ?? app(Pph21CalculationService::class);
    }

    /**
     * Preview THR calculation breakdown for a single employee.
     *
     * @param  array{
     *     target_religion?: string|null,
     *     include_positional_allowance?: bool,
     *     custom_factor?: float|null,
     * }  $options
     * @return array{
     *     employee_id: int,
     *     full_name: string,
     *     nip: string,
     *     religion: ?string,
     *     tenure_months: int,
     *     factor: float,
     *     is_eligible: bool,
     *     basic_salary: float,
     *     fixed_allowance: float,
     *     base_wage: float,
     *     gross_thr: float,
     *     thr_basic: float,
     *     thr_allowance: float,
     *     pph21_tax: float,
     *     net_thr: float,
     * }|null
     */
    public function previewEmployeeThr(
        Employee $employee,
        CarbonInterface|string $cutoffDate,
        array $options = []
    ): ?array {
        $targetReligion = $options['target_religion'] ?? null;

        // If target religion specified and does not match employee religion, skip
        if ($targetReligion && ! empty($employee->religion) && strcasecmp($employee->religion, $targetReligion) !== 0) {
            return null;
        }

        $cutoff = is_string($cutoffDate) ? Carbon::parse($cutoffDate) : Carbon::instance($cutoffDate);

        // Calculate tenure from earliest start date
        $earliestStart = $employee->employments()->min('start_date')
            ?? $employee->currentEmployment?->start_date;

        if (! $earliestStart) {
            return null;
        }

        $startDate = Carbon::parse($earliestStart);
        if ($startDate->isAfter($cutoff)) {
            $tenureMonths = 0;
        } else {
            $tenureMonths = (int) $startDate->diffInMonths($cutoff);
        }

        // Determine factor (Permenaker No. 6/2016)
        if (isset($options['custom_factor']) && is_numeric($options['custom_factor'])) {
            $factor = (float) $options['custom_factor'];
        } elseif ($tenureMonths >= 12) {
            $factor = 1.0;
        } elseif ($tenureMonths >= 1) {
            $factor = round($tenureMonths / 12, 4);
        } else {
            $factor = 0.0;
        }

        $isEligible = $factor > 0.0;

        // Base Salary
        $salaryModel = $employee->currentSalary;
        $basicSalary = $salaryModel ? (float) $salaryModel->basic_salary : 0.0;

        // Fixed Allowance (Positional allowance & active recurring fixed allowances)
        $fixedAllowance = 0.0;
        $includePositional = $options['include_positional_allowance'] ?? true;
        if ($includePositional && $employee->position && $employee->position->default_positional_allowance) {
            $fixedAllowance += (float) $employee->position->default_positional_allowance;
        }

        // Include any active fixed recurring allowances
        $activeComponents = $employee->activeSalaryComponents()->with('component')->get();
        foreach ($activeComponents as $assignedComp) {
            $comp = $assignedComp->component;
            if ($comp && $comp->is_active && $comp->type === 'allowance' && $comp->is_taxable) {
                // If it's a fixed allowance (e.g. Jabatan, Keahlian)
                $fixedAllowance += (float) $assignedComp->amount;
            }
        }

        $baseWage = $basicSalary + $fixedAllowance;

        $thrBasic = round($basicSalary * $factor, 2);
        $thrAllowance = round($fixedAllowance * $factor, 2);
        $grossThr = $thrBasic + $thrAllowance;

        // Calculate PPh 21 on THR (No BPJS deductions)
        $pph21Tax = 0.0;
        if ($grossThr > 0) {
            $taxResult = $this->pph21Service->calculateMonthlyTax(
                employee: $employee,
                basicSalary: $thrBasic,
                taxableAllowances: $thrAllowance,
                employerBpjs: ['kes' => 0.0, 'jkk' => 0.0, 'jkm' => 0.0],
                employeeBpjs: ['jht' => 0.0, 'jp' => 0.0],
                asOfDate: $cutoff
            );
            $pph21Tax = (float) $taxResult['monthly_tax'];
        }

        $netThr = max(0.0, $grossThr - $pph21Tax);

        return [
            'employee_id' => $employee->id,
            'full_name' => $employee->full_name,
            'nip' => $employee->nip,
            'religion' => $employee->religion,
            'tenure_months' => $tenureMonths,
            'factor' => $factor,
            'is_eligible' => $isEligible,
            'basic_salary' => $basicSalary,
            'fixed_allowance' => $fixedAllowance,
            'base_wage' => $baseWage,
            'gross_thr' => $grossThr,
            'thr_basic' => $thrBasic,
            'thr_allowance' => $thrAllowance,
            'pph21_tax' => $pph21Tax,
            'net_thr' => $netThr,
        ];
    }

    /**
     * Compute and generate off-cycle THR items for a payroll run.
     *
     * @param  iterable<Employee>  $employees
     * @param  array{
     *     holiday_name?: string,
     *     target_religion?: string|null,
     *     include_positional_allowance?: bool,
     *     custom_factors?: array<int, float>,
     * }  $options
     */
    public function calculateThrRun(
        PayrollRun $run,
        iterable $employees,
        CarbonInterface|string $cutoffDate,
        array $options = []
    ): void {
        if ($run->isFinalized()) {
            throw new DomainException('Immutability violation: Cannot calculate a finalized payroll run.');
        }

        $cutoff = is_string($cutoffDate) ? Carbon::parse($cutoffDate) : Carbon::instance($cutoffDate);

        $totalGross = 0.0;
        $totalDeductions = 0.0;
        $totalNet = 0.0;
        $processedCount = 0;

        foreach ($employees as $employee) {
            $preview = $this->previewEmployeeThr($employee, $cutoff, $options);

            if (! $preview || ! $preview['is_eligible']) {
                continue;
            }

            $bank = $employee->primaryBankAccount;
            $factorPercent = round($preview['factor'] * 100);

            $item = PayrollItem::create([
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'basic_salary' => $preview['thr_basic'],
                'allowances' => $preview['thr_allowance'],
                'deductions' => 0.00,
                'bpjs_tk_employee' => 0.00,
                'bpjs_tk_employer' => 0.00,
                'bpjs_kes_employee' => 0.00,
                'bpjs_kes_employer' => 0.00,
                'pph21_tax' => $preview['pph21_tax'],
                'net_salary' => $preview['net_thr'],
                'payment_status' => 'pending',
                'bank_name' => $bank?->bank_name,
                'bank_account_number' => $bank?->account_number,
            ]);

            // Add component breakdown
            $item->components()->create([
                'name' => "THR Gaji Pokok ({$factorPercent}%)",
                'type' => 'earning',
                'amount' => $preview['thr_basic'],
            ]);

            if ($preview['thr_allowance'] > 0) {
                $item->components()->create([
                    'name' => "THR Tunjangan Tetap ({$factorPercent}%)",
                    'type' => 'earning',
                    'amount' => $preview['thr_allowance'],
                ]);
            }

            if ($preview['pph21_tax'] > 0) {
                $item->components()->create([
                    'name' => 'PPh 21 atas THR',
                    'type' => 'deduction',
                    'amount' => $preview['pph21_tax'],
                ]);
            }

            $totalGross += $preview['gross_thr'];
            $totalDeductions += $preview['pph21_tax'];
            $totalNet += $preview['net_thr'];
            $processedCount++;
        }

        $metadata = array_merge($run->metadata ?? [], [
            'holiday_name' => $options['holiday_name'] ?? 'Tunjangan Hari Raya',
            'target_religion' => $options['target_religion'] ?? 'all',
            'cutoff_date' => $cutoff->toDateString(),
            'processed_count' => $processedCount,
            'calculated_at' => Carbon::now()->toIso8601String(),
        ]);

        $run->update([
            'type' => 'thr',
            'status' => 'calculated',
            'total_gross_pay' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net_pay' => $totalNet,
            'metadata' => $metadata,
        ]);
    }
}
