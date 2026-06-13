<?php

namespace App\Filament\Widgets;

use App\Enums\ConferenceRoomRequestStatus;
use App\Enums\WorkspaceBookingStatus;
use App\Models\ConferenceRoomRequest;
use App\Models\Event;
use App\Models\Workspace;
use App\Models\WorkspaceBooking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $today = now()->toDateString();

        $workspaceBookingsToday = WorkspaceBooking::query()
            ->whereDate('booking_date', $today)
            ->count();

        $occupiedWorkspaces = WorkspaceBooking::query()
            ->whereDate('booking_date', $today)
            ->where('status', WorkspaceBookingStatus::Active)
            ->distinct('workspace_number')
            ->count('workspace_number');

        $pendingConferenceRequests = ConferenceRoomRequest::query()
            ->where('status', ConferenceRoomRequestStatus::Pending)
            ->count();

        $upcomingEvents = Event::query()
            ->whereDate('event_date', '>=', $today)
            ->count();

        $activeWorkspaces = Workspace::query()
            ->active()
            ->count();

        return [
            Stat::make('Бронирования за день', (string) $workspaceBookingsToday),
            Stat::make('Занятые рабочие места', $occupiedWorkspaces.' / '.$activeWorkspaces),
            Stat::make('Заявки на согласование', (string) $pendingConferenceRequests),
            Stat::make('Ближайшие мероприятия', (string) $upcomingEvents),
        ];
    }
}
