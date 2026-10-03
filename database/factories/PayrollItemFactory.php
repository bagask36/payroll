<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItem>
 */
class PayrollItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'basic_salary' => 6000000.00,
            'allowances' => 500000.00,
            'deductions' => 0.00,
            'bpjs_tk_employee' => 180000.00,
            'bpjs_tk_employer' => 374400.00,
            'bpjs_kes_employee' => 60000.00,
            'bpjs_kes_employer' => 240000.00,
            'pph21_tax' => 50000.00,
            'net_salary' => 6210000.00,
            'payment_status' => 'pending',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
        ];
    }
}
