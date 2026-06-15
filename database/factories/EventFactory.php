<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $events = [
            'Встреча резидентов',
            'Практикум по продукту',
            'Нетворкинг для стартапов',
            'Разбор проектов',
            'Открытая лекция',
        ];

        return [
            'title' => fake()->randomElement($events),
            'description' => fake('ru_RU')->paragraph(),
            'event_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'event_time' => fake()->time('H:i'),
            'banner_path' => null,
        ];
    }
}
