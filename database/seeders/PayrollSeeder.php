<?php

namespace Database\Seeders;

use App\Models\BpjsRule;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Regulatory BPJS Rules 2026
        $bpjsPrograms = [
            [
                'program_name' => 'kesehatan',
                'employee_rate' => 0.0100, // 1%
                'employer_rate' => 0.0400, // 4%
                'wage_ceiling' => 12000000.00,
                'wage_floor' => 4500000.00,
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ],
            [
                'program_name' => 'jht',
                'employee_rate' => 0.0200, // 2%
                'employer_rate' => 0.0370, // 3.7%
                'wage_ceiling' => null,
                'wage_floor' => null,
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ],
            [
                'program_name' => 'jp',
                'employee_rate' => 0.0100, // 1%
                'employer_rate' => 0.0200, // 2%
                'wage_ceiling' => 10042300.00,
                'wage_floor' => null,
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ],
            [
                'program_name' => 'jkk',
                'employee_rate' => 0.0000,
                'employer_rate' => 0.0024, // 0.24%
                'wage_ceiling' => null,
                'wage_floor' => null,
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ],
            [
                'program_name' => 'jkm',
                'employee_rate' => 0.0000,
                'employer_rate' => 0.0030, // 0.30%
                'wage_ceiling' => null,
                'wage_floor' => null,
                'effective_from' => '2026-01-01',
                'is_active' => true,
            ],
        ];

        foreach ($bpjsPrograms as $rule) {
            BpjsRule::updateOrCreate(
                ['program_name' => $rule['program_name'], 'effective_from' => $rule['effective_from']],
                $rule
            );
        }

        // 2. Monthly Payroll Period (October 2026)
        $period = PayrollPeriod::updateOrCreate(
            ['name' => 'Payroll Periode Oktober 2026'],
            [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'cutoff_date' => '2026-10-25',
                'is_closed' => false,
            ]
        );

        // 3. Payroll Run Simulation
        $payrollUser = User::where('email', 'admin@juwitahospital.com')->first() ?? User::factory()->create();

        $run = PayrollRun::where('payroll_period_id', $period->id)
            ->where('run_date', '2026-10-26')
            ->first();

        if (! $run) {
            $run = PayrollRun::create([
                'payroll_period_id' => $period->id,
                'run_date' => '2026-10-26',
                'status' => 'draft',
                'processed_by' => $payrollUser->id,
            ]);
        }

        // Execute deterministic calculation for all active hospital employees only when in draft
        if ($run->status === 'draft') {
            $employees = Employee::active()->with(['currentSalary', 'primaryBankAccount'])->get();
            if ($employees->isNotEmpty()) {
                app(PayrollCalculationService::class)->calculateRun($run, $employees);
            }
        }
    }
}
