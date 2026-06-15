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
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

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
            Stat::make('Бронирования за день', (string) $workspaceBookingsToday)
                ->description('Рабочие места на сегодня')
                ->chart($this->workspaceBookingsTrend())
                ->color('primary'),
            Stat::make('Занятые рабочие места', $occupiedWorkspaces.' / '.$activeWorkspaces)
                ->description($this->occupancyDescription($occupiedWorkspaces, $activeWorkspaces))
                ->color($this->occupancyColor($occupiedWorkspaces, $activeWorkspaces)),
            Stat::make('Заявки на согласование', (string) $pendingConferenceRequests)
                ->description('Конференц-зал ожидает решения')
                ->color($pendingConferenceRequests > 0 ? 'warning' : 'success'),
            Stat::make('Ближайшие мероприятия', (string) $upcomingEvents)
                ->description('Опубликованные анонсы впереди')
                ->color('info'),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function workspaceBookingsTrend(): array
    {
        return collect(range(6, 0))
            ->map(fn (int $daysAgo): int => WorkspaceBooking::query()
                ->whereDate('booking_date', now()->subDays($daysAgo)->toDateString())
                ->count())
            ->all();
    }

    private function occupancyDescription(int $occupiedWorkspaces, int $activeWorkspaces): string
    {
        if ($activeWorkspaces === 0) {
            return 'Активных мест пока нет';
        }

        return round(($occupiedWorkspaces / $activeWorkspaces) * 100).'% от активных мест';
    }

    private function occupancyColor(int $occupiedWorkspaces, int $activeWorkspaces): string
    {
        if ($activeWorkspaces === 0) {
            return 'gray';
        }

        $percent = ($occupiedWorkspaces / $activeWorkspaces) * 100;

        return match (true) {
            $percent >= 85 => 'danger',
            $percent >= 60 => 'warning',
            default => 'success',
        };
    }
}
