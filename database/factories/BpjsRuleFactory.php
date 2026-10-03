<?php

namespace Database\Factories;

use App\Models\BpjsRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BpjsRule>
 */
class BpjsRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_name' => 'kesehatan',
            'employee_rate' => 0.0100,
            'employer_rate' => 0.0400,
            'wage_ceiling' => 12000000.00,
            'wage_floor' => 4500000.00,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
            'is_active' => true,
        ];
    }
}
