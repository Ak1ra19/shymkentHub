<?php

namespace App\Filament\App\Widgets;

use App\Enums\ConferenceRoomRequestStatus;
use App\Enums\WorkspaceBookingStatus;
use App\Models\ConferenceRoomRequest;
use App\Models\Event;
use App\Models\WorkspaceBooking;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserBookingStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 10;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Сводка на сегодня';

    protected ?string $description = 'Главные показатели по вашим бронированиям и событиям хаба.';

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
            ->whereBetween('event_date', [$today, now()->addDays(7)->toDateString()])
            ->count();

        return [
            Stat::make('Мои места сегодня', (string) $activeWorkspaceBookings)
                ->description('Активные брони на текущий день')
                ->descriptionIcon(Heroicon::OutlinedComputerDesktop, IconPosition::Before)
                ->color('warning'),
            Stat::make('На рассмотрении', (string) $pendingConferenceRequests)
                ->description('Заявки по конференц-залу')
                ->descriptionIcon(Heroicon::OutlinedClock, IconPosition::Before)
                ->color('gray'),
            Stat::make('Одобрено', (string) $approvedConferenceRequests)
                ->description('Подтвержденные слоты')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle, IconPosition::Before)
                ->color('success'),
            Stat::make('События недели', (string) $upcomingEvents)
                ->description('Ближайшие мероприятия хаба')
                ->descriptionIcon(Heroicon::OutlinedCalendarDays, IconPosition::Before)
                ->color('info'),
        ];
    }
}
