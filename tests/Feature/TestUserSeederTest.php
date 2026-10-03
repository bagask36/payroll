<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_test_user_with_password_123456(): void
    {
        $this->seed(TestUserSeeder::class);

        $testUser = User::where('email', 'testuser@juwitahospital.com')->first();
        $this->assertNotNull($testUser);
        $this->assertTrue(Hash::check('123456', $testUser->password));
        $this->assertTrue($testUser->isSuperAdmin());

        // Alias testuser@example.com
        $exampleUser = User::where('email', 'testuser@example.com')->first();
        $this->assertNotNull($exampleUser);
        $this->assertTrue(Hash::check('123456', $exampleUser->password));

        // Role accounts
        $hr = User::where('email', 'hr@juwitahospital.com')->first();
        $this->assertNotNull($hr);
        $this->assertTrue(Hash::check('123456', $hr->password));
        $this->assertTrue($hr->hasRole('hr_admin'));

        $doctor = User::where('email', 'doctor@juwitahospital.com')->first();
        $this->assertNotNull($doctor);
        $this->assertTrue(Hash::check('123456', $doctor->password));
        $this->assertTrue($doctor->hasRole('doctor'));
    }
}
