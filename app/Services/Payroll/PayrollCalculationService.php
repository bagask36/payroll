<?php

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\BpjsRule;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Collection;

class PayrollCalculationService
{
    public function __construct(
        protected ?Pph21CalculationService $pph21Service = null
    ) {
        $this->pph21Service = $pph21Service ?? app(Pph21CalculationService::class);
    }

    /**
     * Compute and generate payroll items for an entire payroll run.
     *
     * @param  Collection<int, Employee>  $employees
     */
    public function calculateRun(PayrollRun $run, Collection $employees): void
    {
        $bpjsRules = BpjsRule::active()->get()->keyBy('program_name');

        $totalGross = 0.0;
        $totalDeductions = 0.0;
        $totalNet = 0.0;

        foreach ($employees as $employee) {
            $salaryModel = $employee->currentSalary;
            if (! $salaryModel) {
                continue;
            }

            $basicSalary = (float) $salaryModel->basic_salary;
            $bank = $employee->primaryBankAccount;

            // BPJS Kesehatan
            $kesRule = $bpjsRules->get('kesehatan');
            $kesEmployee = $kesRule ? $kesRule->calculateEmployeeContribution($basicSalary) : 0.0;
            $kesEmployer = $kesRule ? $kesRule->calculateEmployerContribution($basicSalary) : 0.0;

            // BPJS Ketenagakerjaan (JHT + JP + JKK + JKM)
            $jhtRule = $bpjsRules->get('jht');
            $jpRule = $bpjsRules->get('jp');
            $jkkRule = $bpjsRules->get('jkk');
            $jkmRule = $bpjsRules->get('jkm');

            $jhtEmployee = $jhtRule ? $jhtRule->calculateEmployeeContribution($basicSalary) : 0.0;
            $jpEmployee = $jpRule ? $jpRule->calculateEmployeeContribution($basicSalary) : 0.0;
            $jkkEmployer = $jkkRule ? $jkkRule->calculateEmployerContribution($basicSalary) : 0.0;
            $jkmEmployer = $jkmRule ? $jkmRule->calculateEmployerContribution($basicSalary) : 0.0;

            $tkEmployee = $jhtEmployee + $jpEmployee;
            $tkEmployer = ($jhtRule ? $jhtRule->calculateEmployerContribution($basicSalary) : 0.0)
                + ($jpRule ? $jpRule->calculateEmployerContribution($basicSalary) : 0.0)
                + $jkkEmployer
                + $jkmEmployer;

            // Allowances & Deductions
            $allowances = 0.0;
            $taxableAllowances = 0.0;
            $deductions = 0.0;
            $componentBreakdowns = [];

            // Calculate attendance days for the employee in this payroll period (up to cutoff date)
            $period = $run->period;
            $startDate = $period?->start_date;
            $cutoffDate = $period?->cutoff_date ?? $period?->end_date ?? $run->run_date;

            $presentDays = 0;
            if ($startDate && $cutoffDate) {
                $presentDays = Attendance::where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $cutoffDate])
                    ->whereIn('status', ['present', 'late'])
                    ->distinct('date')
                    ->count('date');
            }

            // Fetch employee's active assigned components
            $activeComponents = $employee->activeSalaryComponents()->with('component')->get();
            foreach ($activeComponents as $assignedComp) {
                $compModel = $assignedComp->component;
                if (! $compModel || ! $compModel->is_active) {
                    continue;
                }

                $amt = (float) $assignedComp->amount;
                if ($compModel->type === 'allowance' || $compModel->type === 'earning') {
                    if ($compModel->is_fixed) {
                        // Rutin (Tetap): Diterima penuh bulanan
                        $finalAmt = $amt;
                        $compName = $compModel->name;
                    } else {
                        // Variabel: Dihitung per hari kehadiran valid (present/late)
                        $finalAmt = $amt * $presentDays;
                        $compName = "{$compModel->name} ({$presentDays} hari × Rp " . number_format($amt, 0, ',', '.') . ')';
                    }

                    $allowances += $finalAmt;
                    if ($compModel->is_taxable) {
                        $taxableAllowances += $finalAmt;
                    }
                    $componentBreakdowns[] = [
                        'name' => $compName,
                        'type' => 'allowance',
                        'amount' => $finalAmt,
                    ];
                } elseif ($compModel->type === 'deduction') {
                    $deductions += $amt;
                    $componentBreakdowns[] = [
                        'name' => $compModel->name,
                        'type' => 'deduction',
                        'amount' => $amt,
                    ];
                }
            }

            // Calculate PPh 21 tax withholding
            $taxResult = $this->pph21Service->calculateMonthlyTax(
                employee: $employee,
                basicSalary: $basicSalary,
                taxableAllowances: $taxableAllowances,
                employerBpjs: [
                    'kes' => $kesEmployer,
                    'jkk' => $jkkEmployer,
                    'jkm' => $jkmEmployer,
                ],
                employeeBpjs: [
                    'jht' => $jhtEmployee,
                    'jp' => $jpEmployee,
                ],
                asOfDate: $run->run_date ?? now()
            );

            $pph21Tax = $taxResult['monthly_tax'];

            // Gross pay & Net pay
            $gross = $basicSalary + $allowances;
            $totalEmpDeductions = $deductions + $kesEmployee + $tkEmployee + $pph21Tax;
            $netSalary = max(0.0, $gross - $totalEmpDeductions);

            $item = PayrollItem::create([
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'deductions' => $deductions,
                'bpjs_tk_employee' => $tkEmployee,
                'bpjs_tk_employer' => $tkEmployer,
                'bpjs_kes_employee' => $kesEmployee,
                'bpjs_kes_employer' => $kesEmployer,
                'pph21_tax' => $pph21Tax,
                'net_salary' => $netSalary,
                'payment_status' => 'pending',
                'bank_name' => $bank?->bank_name,
                'bank_account_number' => $bank?->account_number,
            ]);

            foreach ($componentBreakdowns as $breakdown) {
                $item->components()->create($breakdown);
            }

            $totalGross += $gross;
            $totalDeductions += $totalEmpDeductions;
            $totalNet += $netSalary;
        }

        $run->update([
            'status' => 'calculated',
            'total_gross_pay' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net_pay' => $totalNet,
        ]);
    }
}
