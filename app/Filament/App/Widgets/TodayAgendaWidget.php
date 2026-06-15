<?php

namespace App\Filament\App\Widgets;

use App\Models\ConferenceRoomRequest;
use App\Models\WorkspaceBooking;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class TodayAgendaWidget extends Widget
{
    protected static ?int $sort = 20;

    protected static bool $isLazy = false;

    protected string $view = 'filament.app.widgets.today-agenda-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{entries:Collection<int, array{title:string,time:string,status:string,meta:string,tone:string}>}
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $workspaceEntries = WorkspaceBooking::query()
            ->whereBelongsTo($user)
            ->whereDate('booking_date', $today)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (WorkspaceBooking $booking): array => [
                'title' => 'Рабочее место № '.$booking->workspace_number,
                'time' => $booking->starts_at->format('H:i').' - '.$booking->ends_at->format('H:i'),
                'status' => $booking->status->label(),
                'meta' => 'Рабочее место',
                'tone' => 'amber',
            ]);

        $conferenceEntries = ConferenceRoomRequest::query()
            ->whereBelongsTo($user)
            ->whereDate('booking_date', $today)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ConferenceRoomRequest $request): array => [
                'title' => $request->purpose,
                'time' => $request->starts_at->format('H:i').' - '.$request->ends_at->format('H:i'),
                'status' => $request->status->label(),
                'meta' => 'Конференц-зал',
                'tone' => 'sky',
            ]);

        return [
            'entries' => $workspaceEntries
                ->merge($conferenceEntries)
                ->sortBy('time')
                ->values(),
        ];
    }
}
