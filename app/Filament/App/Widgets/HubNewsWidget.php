<?php

namespace App\Filament\App\Widgets;

use App\Models\Event;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class HubNewsWidget extends Widget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected string $view = 'filament.app.widgets.hub-news-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 8,
    ];

    /**
     * @return array{headlineEvent:?Event, events:Collection<int, Event>}
     */
    protected function getViewData(): array
    {
        $events = Event::query()
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->limit(4)
            ->get();

        return [
            'headlineEvent' => $events->shift(),
            'events' => $events,
        ];
    }
}
