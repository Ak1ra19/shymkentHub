<?php

namespace Database\Factories;

use App\Enums\ConferenceRoomRequestStatus;
use App\Models\ConferenceRoomRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConferenceRoomRequest>
 */
class ConferenceRoomRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'booking_date' => now()->toDateString(),
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'purpose' => fake()->sentence(4),
            'status' => ConferenceRoomRequestStatus::Pending,
        ];
    }
}
