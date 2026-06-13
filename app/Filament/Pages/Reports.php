<?php

namespace App\Filament\Pages;

use App\Enums\ConferenceRoomRequestStatus;
use App\Enums\WorkspaceBookingStatus;
use App\Models\ConferenceRoomRequest;
use App\Models\Workspace;
use App\Models\WorkspaceBooking;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Отчеты';

    protected static string|UnitEnum|null $navigationGroup = 'Контроль';

    protected static ?string $title = 'Отчеты';

    protected static ?int $navigationSort = 80;

    protected string $view = 'filament.pages.reports';

    public function getTodayBookingsProperty(): int
    {
        return WorkspaceBooking::query()
            ->whereDate('booking_date', now()->toDateString())
            ->count()
            + ConferenceRoomRequest::query()
                ->whereDate('booking_date', now()->toDateString())
                ->count();
    }

    public function getTodayWorkspaceBookingsProperty(): int
    {
        return WorkspaceBooking::query()
            ->whereDate('booking_date', now()->toDateString())
            ->count();
    }

    public function getOccupiedWorkspacesProperty(): int
    {
        return WorkspaceBooking::query()
            ->whereDate('booking_date', now()->toDateString())
            ->where('status', WorkspaceBookingStatus::Active)
            ->distinct('workspace_number')
            ->count('workspace_number');
    }

    public function getActiveWorkspacesProperty(): int
    {
        return Workspace::query()
            ->active()
            ->count();
    }

    public function getPendingConferenceRequestsProperty(): int
    {
        return ConferenceRoomRequest::query()
            ->where('status', ConferenceRoomRequestStatus::Pending)
            ->count();
    }
}
