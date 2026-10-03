<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'title' => fake()->jobTitle(),
            'grade_level' => 'staff',
        ];
    }
}
