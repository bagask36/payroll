<?php

namespace Database\Factories;

use App\Models\GeofenceLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeofenceLocation>
 */
class GeofenceLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'RS Permata Bunda Kampus Utama',
            'latitude' => -6.91746400,
            'longitude' => 107.61912200,
            'radius_meters' => 100,
            'is_active' => true,
        ];
    }
}
