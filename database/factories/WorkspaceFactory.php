<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(1, 60),
            'label' => null,
            'zone' => 'Общий зал',
            'sort_order' => fake()->numberBetween(1, 60),
            'is_active' => true,
            'assigned_user_id' => null,
        ];
    }
}
