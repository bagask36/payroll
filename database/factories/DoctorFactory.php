<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
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
            'employee_id' => null,
            'name' => 'dr. '.fake()->name(),
            'specialization' => fake()->randomElement([
                'Dokter Umum',
                'Spesialis Penyakit Dalam (Sp.PD)',
                'Spesialis Anak (Sp.A)',
                'Spesialis Bedah (Sp.B)',
                'Spesialis Obstetri & Ginekologi (Sp.OG)',
                'Spesialis Jantung & Pembuluh Darah (Sp.JP)',
            ]),
            'str_number' => 'STR-'.fake()->unique()->numerify('##############'),
            'str_valid_until' => fake()->dateTimeBetween('+1 year', '+5 years')->format('Y-m-d'),
            'sip_number' => 'SIP/'.fake()->numerify('####/RSJ/####'),
            'sip_valid_until' => fake()->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
            'phone_number' => '08'.fake()->numerify('##########'),
            'status' => 'active',
        ];
    }

    /**
     * Attach a user portal account.
     */
    public function withUser(?User $user = null): static
    {
        return $this->state(function () use ($user) {
            $userInstance = $user ?? User::factory()->create();
            $userInstance->assignRole('doctor');

            return [
                'user_id' => $userInstance->id,
                'name' => $userInstance->name,
            ];
        });
    }

    /**
     * Attach an organic employee link.
     */
    public function withEmployee(?Employee $employee = null): static
    {
        return $this->state(fn () => [
            'employee_id' => ($employee ?? Employee::factory()->create())->id,
        ]);
    }
}
