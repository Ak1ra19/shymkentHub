<?php

namespace App\Services;

use App\Enums\ConferenceRoomRequestStatus;
use App\Models\ConferenceRoomRequest;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

class ConferenceRoomAvailability
{
    private const DAY_START = '09:00';

    private const DAY_END = '18:00';

    private const DEFAULT_SLOT_MINUTES = 60;

    private const TIME_STEP_MINUTES = 30;

    /**
     * @return array{starts_at:string, ends_at:string}|null
     */
    public function firstAvailableSlot(mixed $date, ?string $preferredStart = null, int $minutes = self::DEFAULT_SLOT_MINUTES): ?array
    {
        $date = $this->dateValue($date);
        $dayStart = $this->dateTimeValue($date, self::DAY_START);
        $dayEnd = $this->dateTimeValue($date, self::DAY_END);
        $candidate = $this->dateTimeValue($date, $this->timeValue($preferredStart, self::DAY_START));

        if ($candidate->lessThan($dayStart)) {
            $candidate = $dayStart;
        }

        foreach ($this->mergedIntervals($date) as $interval) {
            if ($interval['ends_at']->lessThanOrEqualTo($candidate)) {
                continue;
            }

            if ($candidate->addMinutes($minutes)->lessThanOrEqualTo($interval['starts_at'])) {
                return $this->slotValue($candidate, $minutes, $dayEnd);
            }

            if ($candidate->lessThan($interval['ends_at'])) {
                $candidate = $interval['ends_at'];
            }
        }

        return $this->slotValue($candidate, $minutes, $dayEnd);
    }

    /**
     * @return array<string, string>
     */
    public function startTimeOptions(mixed $date, int $minutes = self::DEFAULT_SLOT_MINUTES): array
    {
        $date = $this->dateValue($date);
        $dayStart = $this->dateTimeValue($date, self::DAY_START);
        $dayEnd = $this->dateTimeValue($date, self::DAY_END);
        $intervals = $this->mergedIntervals($date);
        $options = [];

        for ($candidate = $dayStart; $candidate->addMinutes($minutes)->lessThanOrEqualTo($dayEnd); $candidate = $candidate->addMinutes(self::TIME_STEP_MINUTES)) {
            $endsAt = $candidate->addMinutes($minutes);

            if (! $this->slotOverlapsIntervals($candidate, $endsAt, $intervals)) {
                $time = $candidate->format('H:i');
                $options[$time] = $time;
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function endTimeOptions(mixed $date, mixed $startsAt): array
    {
        $date = $this->dateValue($date);
        $startsAt = $this->timeValue($startsAt, self::DAY_START);
        $start = $this->dateTimeValue($date, $startsAt);
        $dayStart = $this->dateTimeValue($date, self::DAY_START);

        if ($start->lessThan($dayStart)) {
            $start = $dayStart;
        }

        $boundary = $this->nextBusyBoundary(
            $start,
            $this->dateTimeValue($date, self::DAY_END),
            $this->mergedIntervals($date),
        );

        if ($boundary->lessThanOrEqualTo($start)) {
            return [];
        }

        $options = [];

        for ($candidate = $start->addMinutes(self::TIME_STEP_MINUTES); $candidate->lessThanOrEqualTo($boundary); $candidate = $candidate->addMinutes(self::TIME_STEP_MINUTES)) {
            $time = $candidate->format('H:i');
            $options[$time] = $time;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public function summary(mixed $date): array
    {
        $date = $this->dateValue($date);
        $busyLabel = $this->dailyLabel($date);
        $slot = $this->firstAvailableSlot($date);

        return [
            $busyLabel ? 'Занято: '.$busyLabel : 'На выбранный день заявок нет.',
            $slot
                ? 'Ближайший свободный час: '.$slot['starts_at'].' - '.$slot['ends_at'].'.'
                : 'Свободного часового окна до '.self::DAY_END.' нет.',
        ];
    }

    public function dailyLabel(mixed $date): ?string
    {
        $date = $this->dateValue($date);

        $label = $this->activeRequests($date)
            ->map(fn (ConferenceRoomRequest $request): string => $request->starts_at->format('H:i').' - '.$request->ends_at->format('H:i'))
            ->implode(', ');

        return filled($label) ? $label : null;
    }

    /**
     * @return Collection<int, array{starts_at:CarbonImmutable, ends_at:CarbonImmutable}>
     */
    private function mergedIntervals(string $date): Collection
    {
        $merged = [];

        foreach ($this->intervals($date) as $interval) {
            $lastIndex = array_key_last($merged);

            if ($lastIndex === null || $interval['starts_at']->greaterThan($merged[$lastIndex]['ends_at'])) {
                $merged[] = $interval;

                continue;
            }

            if ($interval['ends_at']->greaterThan($merged[$lastIndex]['ends_at'])) {
                $merged[$lastIndex]['ends_at'] = $interval['ends_at'];
            }
        }

        return collect($merged);
    }

    /**
     * @return Collection<int, array{starts_at:CarbonImmutable, ends_at:CarbonImmutable}>
     */
    private function intervals(string $date): Collection
    {
        return $this->activeRequests($date)
            ->map(fn (ConferenceRoomRequest $request): array => [
                'starts_at' => $this->dateTimeValue($date, $request->starts_at->format('H:i')),
                'ends_at' => $this->dateTimeValue($date, $request->ends_at->format('H:i')),
            ]);
    }

    /**
     * @return Collection<int, ConferenceRoomRequest>
     */
    private function activeRequests(string $date): Collection
    {
        return ConferenceRoomRequest::query()
            ->whereDate('booking_date', $date)
            ->whereIn('status', [
                ConferenceRoomRequestStatus::Pending,
                ConferenceRoomRequestStatus::Approved,
            ])
            ->orderBy('starts_at')
            ->get(['starts_at', 'ends_at']);
    }

    /**
     * @return array{starts_at:string, ends_at:string}|null
     */
    private function slotValue(CarbonImmutable $startsAt, int $minutes, CarbonImmutable $dayEnd): ?array
    {
        $endsAt = $startsAt->addMinutes($minutes);

        if ($endsAt->greaterThan($dayEnd)) {
            return null;
        }

        return [
            'starts_at' => $startsAt->format('H:i'),
            'ends_at' => $endsAt->format('H:i'),
        ];
    }

    /**
     * @param  Collection<int, array{starts_at:CarbonImmutable, ends_at:CarbonImmutable}>  $intervals
     */
    private function slotOverlapsIntervals(CarbonImmutable $startsAt, CarbonImmutable $endsAt, Collection $intervals): bool
    {
        return $intervals->contains(
            fn (array $interval): bool => $startsAt->lessThan($interval['ends_at']) && $endsAt->greaterThan($interval['starts_at']),
        );
    }

    /**
     * @param  Collection<int, array{starts_at:CarbonImmutable, ends_at:CarbonImmutable}>  $intervals
     */
    private function nextBusyBoundary(CarbonImmutable $startsAt, CarbonImmutable $dayEnd, Collection $intervals): CarbonImmutable
    {
        foreach ($intervals as $interval) {
            if ($interval['ends_at']->lessThanOrEqualTo($startsAt)) {
                continue;
            }

            if ($startsAt->lessThan($interval['starts_at'])) {
                return $interval['starts_at'];
            }

            return $startsAt;
        }

        return $dayEnd;
    }

    private function dateValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->toDateString();
        }

        if (blank($value)) {
            return now()->toDateString();
        }

        return CarbonImmutable::parse((string) $value)->toDateString();
    }

    private function timeValue(mixed $value, string $fallback): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        if (blank($value)) {
            return $fallback;
        }

        return mb_substr((string) $value, 0, 5);
    }

    private function dateTimeValue(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', $date.' '.$time);
    }
}
