<?php

namespace App\Filament\App\Widgets;

use App\Enums\ConferenceRoomRequestStatus;
use App\Enums\WorkspaceBookingStatus;
use App\Models\ConferenceRoomRequest;
use App\Models\Event;
use App\Models\WorkspaceBooking;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserBookingStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $activeWorkspaceBookings = WorkspaceBooking::query()
            ->whereBelongsTo($user)
            ->whereDate('booking_date', $today)
            ->where('status', WorkspaceBookingStatus::Active)
            ->count();

        $pendingConferenceRequests = ConferenceRoomRequest::query()
            ->whereBelongsTo($user)
            ->where('status', ConferenceRoomRequestStatus::Pending)
            ->count();

        $approvedConferenceRequests = ConferenceRoomRequest::query()
            ->whereBelongsTo($user)
            ->where('status', ConferenceRoomRequestStatus::Approved)
            ->count();

        $upcomingEvents = Event::query()
            ->whereDate('event_date', '>=', $today)
            ->count();

        return [
            Stat::make('Мои места сегодня', (string) $activeWorkspaceBookings),
            Stat::make('Заявки на рассмотрении', (string) $pendingConferenceRequests),
            Stat::make('Одобренные заявки', (string) $approvedConferenceRequests),
            Stat::make('Ближайшие мероприятия', (string) $upcomingEvents),
        ];
    }
}
