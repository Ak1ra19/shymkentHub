<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'event' => 'test.event',
            'subject_type' => null,
            'subject_id' => null,
            'properties' => [],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
        ];
    }
}
