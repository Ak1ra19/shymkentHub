<?php

namespace Database\Factories;

use App\Enums\WorkspaceBookingStatus;
use App\Models\User;
use App\Models\WorkspaceBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceBooking>
 */
class WorkspaceBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workspace_id' => null,
            'workspace_number' => fake()->numberBetween(1, 60),
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '10:00',
            'status' => WorkspaceBookingStatus::Active,
        ];
    }
}
