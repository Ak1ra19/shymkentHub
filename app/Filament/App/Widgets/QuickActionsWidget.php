<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Resources\ConferenceRoomRequests\ConferenceRoomRequestResource;
use App\Filament\App\Resources\WorkspaceBookings\WorkspaceBookingResource;
use App\Models\Workspace;
use App\Models\WorkspaceBooking;
use App\Services\ConferenceRoomAvailability;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected string $view = 'filament.app.widgets.quick-actions-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 4,
    ];

    /**
     * @return array<string, int|string|null>
     */
    protected function getViewData(): array
    {
        $activeWorkspaces = Workspace::query()->active()->count();
        $occupiedNow = WorkspaceBooking::query()
            ->whereDate('booking_date', now()->toDateString())
            ->where('status', 'active')
            ->where('starts_at', '<', now()->format('H:i'))
            ->where('ends_at', '>', now()->format('H:i'))
            ->distinct('workspace_number')
            ->count('workspace_number');

        $nextConferenceSlot = app(ConferenceRoomAvailability::class)->firstAvailableSlot(now()->toDateString());

        return [
            'workspaceUrl' => WorkspaceBookingResource::getUrl('index', panel: 'app'),
            'conferenceUrl' => ConferenceRoomRequestResource::getUrl('index', panel: 'app'),
            'residentInstructionsUrl' => route('resident-instructions'),
            'availableWorkspacesNow' => max($activeWorkspaces - $occupiedNow, 0),
            'activeWorkspaces' => $activeWorkspaces,
            'nextConferenceSlot' => $nextConferenceSlot
                ? $nextConferenceSlot['starts_at'].' - '.$nextConferenceSlot['ends_at']
                : null,
        ];
    }
}
