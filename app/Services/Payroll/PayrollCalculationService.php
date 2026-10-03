<?php

namespace App\Services\Payroll;

use App\Models\BpjsRule;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Collection;

class PayrollCalculationService
{
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

            $tkEmployee = ($jhtRule ? $jhtRule->calculateEmployeeContribution($basicSalary) : 0.0)
                + ($jpRule ? $jpRule->calculateEmployeeContribution($basicSalary) : 0.0);

            $tkEmployer = ($jhtRule ? $jhtRule->calculateEmployerContribution($basicSalary) : 0.0)
                + ($jpRule ? $jpRule->calculateEmployerContribution($basicSalary) : 0.0)
                + ($jkkRule ? $jkkRule->calculateEmployerContribution($basicSalary) : 0.0)
                + ($jkmRule ? $jkmRule->calculateEmployerContribution($basicSalary) : 0.0);

            // Allowances & Deductions
            $allowances = 0.0;
            $deductions = 0.0;

            // Simple gross pay
            $gross = $basicSalary + $allowances;
            $totalEmpDeductions = $deductions + $kesEmployee + $tkEmployee;
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
                'pph21_tax' => 0.00,
                'net_salary' => $netSalary,
                'payment_status' => 'pending',
                'bank_name' => $bank?->bank_name,
                'bank_account_number' => $bank?->account_number,
            ]);

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
