<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRun>
 */
class PayrollRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'run_date' => now()->toDateString(),
            'status' => 'draft',
            'total_gross_pay' => 0.00,
            'total_deductions' => 0.00,
            'total_net_pay' => 0.00,
            'processed_by' => User::factory(),
            'finalized_by' => null,
            'finalized_at' => null,
        ];
    }
}
