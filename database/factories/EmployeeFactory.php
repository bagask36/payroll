<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'unit_id' => Unit::factory(),
            'position_id' => Position::factory(),
            'nip' => 'PEG-'.fake()->unique()->numerify('######'),
            'ktp_number' => fake()->numerify('3201##############'),
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement(['male', 'female']),
            'education_level' => fake()->randomElement(['SMA/SMK', 'D3', 'S1', 'S2', 'Profesi']),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-20 years')->format('Y-m-d'),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced']),
            'religion' => fake()->randomElement(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'khonghucu']),
            'tax_dependents' => fake()->numberBetween(0, 3),
            'npwp_number' => fake()->numerify('##.###.###.#-###.###'),
            'phone_number' => '08'.fake()->numerify('##########'),
            'residential_address' => fake()->address(),
            'face_biometric_vector' => null,
            'status' => 'active',
        ];
    }

    /**
     * Associate an existing or new user account with employee role.
     */
    public function withUser(?User $user = null): static
    {
        return $this->state(function () use ($user) {
            $userInstance = $user ?? User::factory()->create();
            $userInstance->assignRole('employee');

            return [
                'user_id' => $userInstance->id,
                'full_name' => $userInstance->name,
            ];
        });
    }
}
