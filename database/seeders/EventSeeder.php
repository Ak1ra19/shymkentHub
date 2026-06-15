<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'title' => 'Встреча резидентов ShymkentHub',
                'description' => 'Открытая встреча для знакомства резидентов, обмена идеями и обсуждения ближайших возможностей хаба.',
                'event_date' => now()->toDateString(),
                'event_time' => '18:30',
                'banner_path' => 'events/startup-meetup.jpg',
            ],
            [
                'title' => 'Практикум по запуску продукта',
                'description' => 'Разбор пути от идеи до первого запуска: гипотезы, интервью, прототип и первые пользователи.',
                'event_date' => now()->addDay()->toDateString(),
                'event_time' => '16:00',
                'banner_path' => 'events/product-workshop.jpg',
            ],
            [
                'title' => 'Питч-сессия локальных проектов',
                'description' => 'Команды коротко презентуют проекты и получают обратную связь от приглашенных экспертов и резидентов.',
                'event_date' => now()->addDay()->toDateString(),
                'event_time' => '10:30',
                'banner_path' => 'events/startup-meetup.jpg',
            ],
            [
                'title' => 'Нетворкинг для новых участников',
                'description' => 'Неформальная встреча для новых резидентов: знакомство с пространством, командами и правилами работы в хабе.',
                'event_date' => now()->addDay()->toDateString(),
                'event_time' => '12:00',
                'banner_path' => 'events/startup-meetup.jpg',
            ],
        ];

        foreach ($events as $event) {
            Event::query()->updateOrCreate(
                ['title' => $event['title']],
                $event,
            );
        }
    }
}
