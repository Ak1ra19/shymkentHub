<?php

namespace Database\Factories;

use App\Models\WorkspaceScheduleSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceScheduleSetting>
 */
class WorkspaceScheduleSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'starts_on' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'note' => fake()->optional()->sentence(4),
        ];
    }
}
