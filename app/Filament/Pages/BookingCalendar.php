<?php

namespace App\Filament\Pages;

use App\Models\ConferenceRoomRequest;
use App\Models\WorkspaceBooking;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class BookingCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Календарь броней';

    protected static string|UnitEnum|null $navigationGroup = 'Бронирования';

    protected static ?string $title = 'Календарь броней';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.booking-calendar';

    public string $selectedDate = '';

    public string $fromDate;

    public string $toDate;

    public string $bookingType = 'all';

    public function mount(): void
    {
        $this->fromDate = now()->toDateString();
        $this->toDate = now()->addDays(7)->toDateString();
    }

    public function setToday(): void
    {
        $this->selectedDate = now()->toDateString();
        $this->fromDate = $this->selectedDate;
        $this->toDate = $this->selectedDate;
    }

    public function clearSelectedDate(): void
    {
        $this->selectedDate = '';
    }

    public function updatedSelectedDate(?string $value): void
    {
        if (blank($value)) {
            return;
        }

        $date = CarbonImmutable::parse($value)->toDateString();
        $this->fromDate = $date;
        $this->toDate = $date;
    }

    public function updatedFromDate(): void
    {
        $this->selectedDate = '';
    }

    public function updatedToDate(): void
    {
        $this->selectedDate = '';
    }

    /**
     * @return Collection<int, array{date:string, time:string, type:string, resource:string, user:string, company:string, status:string, tone:string, meta:string}>
     */
    public function getEntriesProperty(): Collection
    {
        [$from, $to] = $this->normalizedPeriod();

        $entries = collect();

        if (in_array($this->bookingType, ['all', 'workspace'], true)) {
            $entries = $entries->merge(
                WorkspaceBooking::query()
                    ->with('user:id,name,company')
                    ->whereBetween('booking_date', [$from, $to])
                    ->orderBy('booking_date')
                    ->orderBy('starts_at')
                    ->get()
                    ->map(fn (WorkspaceBooking $booking): array => [
                        'date' => $booking->booking_date->toDateString(),
                        'time' => $booking->starts_at->format('H:i').' - '.$booking->ends_at->format('H:i'),
                        'type' => 'Рабочее место',
                        'resource' => 'Место № '.$booking->workspace_number,
                        'user' => $booking->user->name,
                        'company' => (string) $booking->user->company,
                        'status' => $booking->status->label(),
                        'tone' => 'teal',
                        'meta' => 'Рабочая зона',
                    ])
            );
        }

        if (in_array($this->bookingType, ['all', 'conference'], true)) {
            $entries = $entries->merge(
                ConferenceRoomRequest::query()
                    ->with('user:id,name,company')
                    ->whereBetween('booking_date', [$from, $to])
                    ->orderBy('booking_date')
                    ->orderBy('starts_at')
                    ->get()
                    ->map(fn (ConferenceRoomRequest $request): array => [
                        'date' => $request->booking_date->toDateString(),
                        'time' => $request->starts_at->format('H:i').' - '.$request->ends_at->format('H:i'),
                        'type' => 'Конференц-зал',
                        'resource' => $request->purpose,
                        'user' => $request->user->name,
                        'company' => (string) $request->user->company,
                        'status' => $request->status->label(),
                        'tone' => 'indigo',
                        'meta' => 'До 12 человек',
                    ])
            );
        }

        return $entries
            ->sortBy(['date', 'time'])
            ->values();
    }

    /**
     * @return Collection<string, Collection<int, array<string, string>>>
     */
    public function getGroupedEntriesProperty(): Collection
    {
        return $this->entries->groupBy('date');
    }

    public function getPeriodLabelProperty(): string
    {
        [$from, $to] = $this->normalizedPeriod();

        $fromLabel = CarbonImmutable::parse($from)->translatedFormat('d F');
        $toLabel = CarbonImmutable::parse($to)->translatedFormat('d F');

        return $from === $to ? $fromLabel : $fromLabel.' - '.$toLabel;
    }

    /**
     * @return array{total:int, workspace:int, conference:int, pending:int}
     */
    public function getSummaryProperty(): array
    {
        $entries = $this->entries;

        return [
            'total' => $entries->count(),
            'workspace' => $entries->where('type', 'Рабочее место')->count(),
            'conference' => $entries->where('type', 'Конференц-зал')->count(),
            'pending' => $entries->where('status', 'На рассмотрении')->count(),
        ];
    }

    /**
     * @return array{0:string, 1:string}
     */
    private function normalizedPeriod(): array
    {
        $from = CarbonImmutable::parse($this->fromDate)->toDateString();
        $to = CarbonImmutable::parse($this->toDate)->toDateString();

        if ($from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }
}
