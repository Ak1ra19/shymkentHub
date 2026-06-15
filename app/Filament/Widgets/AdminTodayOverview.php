<?php

namespace App\Filament\Widgets;

use App\Enums\ConferenceRoomRequestStatus;
use App\Enums\WorkspaceBookingStatus;
use App\Models\ConferenceRoomRequest;
use App\Models\WorkspaceBooking;
use App\Services\WorkspaceAvailability;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class AdminTodayOverview extends Widget
{
    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.admin-today-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{scheduleLabel:string, entries:Collection<int, array{time:string, title:string, meta:string, status:string, tone:string}>, pendingRequests:Collection<int, array{time:string, purpose:string, user:string}>}
     */
    protected function getViewData(): array
    {
        $today = now()->toDateString();

        $workspaceEntries = WorkspaceBooking::query()
            ->with('user:id,name,company')
            ->whereDate('booking_date', $today)
            ->where('status', WorkspaceBookingStatus::Active)
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->map(fn (WorkspaceBooking $booking): array => [
                'time' => $booking->starts_at->format('H:i').' - '.$booking->ends_at->format('H:i'),
                'title' => 'Место № '.$booking->workspace_number,
                'meta' => $booking->user->name.' · '.($booking->user->company ?: 'Без компании'),
                'status' => $booking->status->label(),
                'tone' => 'teal',
            ]);

        $conferenceEntries = ConferenceRoomRequest::query()
            ->with('user:id,name,company')
            ->whereDate('booking_date', $today)
            ->whereIn('status', [ConferenceRoomRequestStatus::Pending->value, ConferenceRoomRequestStatus::Approved->value])
            ->orderBy('starts_at')
            ->limit(6)
            ->get()
            ->map(fn (ConferenceRoomRequest $request): array => [
                'time' => $request->starts_at->format('H:i').' - '.$request->ends_at->format('H:i'),
                'title' => $request->purpose,
                'meta' => $request->user->name.' · '.($request->user->company ?: 'Без компании'),
                'status' => $request->status->label(),
                'tone' => 'indigo',
            ]);

        return [
            'scheduleLabel' => app(WorkspaceAvailability::class)->scheduleForDate($today)['label'],
            'entries' => $workspaceEntries
                ->merge($conferenceEntries)
                ->sortBy('time')
                ->take(8)
                ->values(),
            'pendingRequests' => ConferenceRoomRequest::query()
                ->with('user:id,name')
                ->where('status', ConferenceRoomRequestStatus::Pending)
                ->orderBy('booking_date')
                ->orderBy('starts_at')
                ->limit(4)
                ->get()
                ->map(fn (ConferenceRoomRequest $request): array => [
                    'time' => $request->booking_date->format('d.m').' · '.$request->starts_at->format('H:i'),
                    'purpose' => $request->purpose,
                    'user' => $request->user->name,
                ]),
        ];
    }
}
