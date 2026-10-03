<?php

namespace Database\Factories;

use App\Models\PayrollComponent;
use App\Models\PayrollItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollComponent>
 */
class PayrollComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_item_id' => PayrollItem::factory(),
            'name' => 'Tunjangan Fungsional',
            'type' => 'allowance',
            'amount' => 500000.00,
        ];
    }
}
