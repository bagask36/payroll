<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SH-###')),
            'name' => 'Shift '.fake()->word(),
            'start_time' => '07:00:00',
            'end_time' => '14:00:00',
            'is_overnight' => false,
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ];
    }

    /**
     * Overnight shift state.
     */
    public function overnight(): static
    {
        return $this->state(fn () => [
            'name' => 'Shift Malam',
            'start_time' => '21:00:00',
            'end_time' => '07:00:00',
            'is_overnight' => true,
        ]);
    }
}
