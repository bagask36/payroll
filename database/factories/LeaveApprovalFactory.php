<?php

namespace Database\Factories;

use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveApproval>
 */
class LeaveApprovalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_request_id' => LeaveRequest::factory(),
            'approver_id' => User::factory(),
            'tier' => 1,
            'status' => 'approved',
            'notes' => 'Disetujui untuk operasional aman',
            'action_at' => now(),
        ];
    }
}
