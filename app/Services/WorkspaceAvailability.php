<?php

namespace App\Services;

use App\Enums\WorkspaceBookingStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBooking;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

class WorkspaceAvailability
{
    private const DEFAULT_SLOT_MINUTES = 60;

    private const TIME_STEP_MINUTES = 30;

    public function __construct(
        private readonly WorkspaceScheduleService $workspaceSchedule,
    ) {}

    /**
     * @return array{starts_at:string, ends_at:string, label:string, setting:mixed}
     */
    public function scheduleForDate(mixed $date): array
    {
        return $this->workspaceSchedule->forDate($date);
    }

    /**
     * @return array{starts_at:string, ends_at:string}
     */
    public function fullDaySlot(mixed $date): array
    {
        return $this->workspaceSchedule->fullDaySlot($date);
    }

    /**
     * @return Collection<int, Workspace>
     */
    public function activeWorkspaces(?User $user = null): Collection
    {
        return Workspace::query()
            ->with('assignedUser:id,name')
            ->active()
            ->when($user, function ($query) use ($user): void {
                $query->where(function ($query) use ($user): void {
                    $query
                        ->whereNull('assigned_user_id')
                        ->orWhere('assigned_user_id', $user->id);
                });
            })
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, array{id:int, number:int, label:string, zone:?string, status:string, status_label:string, description:string, next_slot:?array{starts_at:string, ends_at:string}, schedule:array<int, string>, can_book:bool, assigned_to_current_user:bool}>
     */
    public function hallMapForUser(User $user, mixed $date): Collection
    {
        $date = $this->dateValue($date);
        $daySchedule = $this->scheduleForDate($date);
        $workspaces = $this->activeWorkspaces($user);
        $workspaceIdsByNumber = $workspaces->pluck('id', 'number');

        $bookingsByWorkspaceId = WorkspaceBooking::query()
            ->with('user:id,name')
            ->whereDate('booking_date', $date)
            ->where('status', WorkspaceBookingStatus::Active)
            ->orderBy('starts_at')
            ->get()
            ->groupBy(function (WorkspaceBooking $booking) use ($workspaceIdsByNumber): ?int {
                if ($booking->workspace_id !== null) {
                    return $booking->workspace_id;
                }

                $workspaceId = $workspaceIdsByNumber->get($booking->workspace_number);

                return $workspaceId === null ? null : (int) $workspaceId;
            });

        return $workspaces
            ->map(function (Workspace $workspace) use ($bookingsByWorkspaceId, $date, $daySchedule, $user): array {
                /** @var Collection<int, WorkspaceBooking> $workspaceBookings */
                $workspaceBookings = $bookingsByWorkspaceId->get($workspace->id, collect());
                $intervals = $workspaceBookings->map(fn (WorkspaceBooking $booking): array => [
                    'starts_at' => $this->dateTimeValue($date, $booking->starts_at->format('H:i')),
                    'ends_at' => $this->dateTimeValue($date, $booking->ends_at->format('H:i')),
                ]);

                $slot = $this->firstAvailableSlotFromIntervals($intervals, $date, schedule: $daySchedule);
                $hasOwnBooking = $workspaceBookings->contains(fn (WorkspaceBooking $booking): bool => $booking->user_id === $user->id);
                $hasOwnFullDayBooking = $workspaceBookings->contains(
                    fn (WorkspaceBooking $booking): bool => $booking->user_id === $user->id && $this->bookingCoversSchedule($booking, $date, $daySchedule),
                );
                $scheduleIsCovered = $this->scheduleIsCovered($intervals, $date, $daySchedule);
                $schedule = $workspaceBookings
                    ->map(fn (WorkspaceBooking $booking): string => $booking->starts_at->format('H:i').' - '.$booking->ends_at->format('H:i').' · '.($booking->user?->name ?? 'Пользователь'))
                    ->values()
                    ->all();

                if ($hasOwnFullDayBooking) {
                    $status = 'mine_full';
                    $statusLabel = 'Ваше на весь день';
                    $description = 'Место закреплено за вами на весь рабочий день: '.$daySchedule['label'].'.';
                } elseif ($hasOwnBooking) {
                    $status = 'mine';
                    $statusLabel = 'Ваше бронирование';
                    $description = 'У вас есть бронь: '.$workspaceBookings
                        ->where('user_id', $user->id)
                        ->map(fn (WorkspaceBooking $booking): string => $booking->starts_at->format('H:i').' - '.$booking->ends_at->format('H:i'))
                        ->implode(', ');
                } elseif ($slot === null) {
                    $status = $scheduleIsCovered ? 'occupied_full' : 'occupied';
                    $statusLabel = $scheduleIsCovered ? 'Занято весь день' : 'Занято';
                    $description = 'Свободного часового окна до '.$daySchedule['ends_at'].' нет.';
                } elseif ($workspaceBookings->isNotEmpty()) {
                    $status = 'partial';
                    $statusLabel = 'Частично занято';
                    $description = 'Ближайшее окно: '.$slot['starts_at'].' - '.$slot['ends_at'].'.';
                } else {
                    $status = 'free';
                    $statusLabel = 'Свободно';
                    $description = 'Свободно весь день '.$daySchedule['label'].'. Ближайшее окно: '.$slot['starts_at'].' - '.$slot['ends_at'].'.';
                }

                return [
                    'id' => $workspace->id,
                    'number' => $workspace->number,
                    'label' => $workspace->displayName(),
                    'zone' => $workspace->zone,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'description' => $description,
                    'next_slot' => $slot,
                    'schedule' => $schedule,
                    'can_book' => $slot !== null,
                    'assigned_to_current_user' => $workspace->assigned_user_id === $user->id,
                ];
            })
            ->values();
    }

    /**
     * @return array<int, string>
     */
    public function optionsForSelection(mixed $date, mixed $startsAt = null, mixed $endsAt = null, ?User $user = null): array
    {
        $date = $this->dateValue($date);
        $daySchedule = $this->scheduleForDate($date);
        $startsAt = filled($startsAt) ? $this->timeValue($startsAt, $daySchedule['starts_at']) : null;
        $endsAt = filled($endsAt) ? $this->timeValue($endsAt, $this->defaultEndTime($date, $startsAt ?? $daySchedule['starts_at'], $daySchedule)) : null;
        $currentLabels = filled($startsAt) && filled($endsAt) && $startsAt < $endsAt
            ? $this->intervalLabelsByWorkspaceId($date, $startsAt, $endsAt)
            : [];

        $dailyLabels = $this->dailyLabelsByWorkspaceId($date);

        return $this->activeWorkspaces($user)
            ->mapWithKeys(function (Workspace $workspace) use ($currentLabels, $dailyLabels, $date, $user): array {
                $details = [];
                $slot = $this->firstAvailableSlotForWorkspace($workspace, $date);

                if (filled($workspace->zone)) {
                    $details[] = $workspace->zone;
                }

                if ($workspace->assignedUser !== null) {
                    $details[] = $user && $workspace->assigned_user_id === $user->id
                        ? 'закреплено за вами'
                        : 'закреплено за '.$workspace->assignedUser->name;
                }

                if (array_key_exists($workspace->id, $currentLabels)) {
                    $details[] = 'занято '.$currentLabels[$workspace->id];
                } elseif (array_key_exists($workspace->id, $dailyLabels)) {
                    $details[] = 'занято '.$dailyLabels[$workspace->id];
                } else {
                    $details[] = 'свободно весь день';
                }

                $details[] = $slot
                    ? 'ближайшее '.$slot['starts_at'].' - '.$slot['ends_at']
                    : 'нет свободного часа';

                return [
                    $workspace->id => $workspace->displayName().' · '.implode(' · ', $details),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function occupiedWorkspaceIds(mixed $date, mixed $startsAt, mixed $endsAt): array
    {
        $date = $this->dateValue($date);
        $daySchedule = $this->scheduleForDate($date);
        $startsAt = $this->timeValue($startsAt, $daySchedule['starts_at']);
        $endsAt = $this->timeValue($endsAt, $this->defaultEndTime($date, $startsAt, $daySchedule));

        if ($startsAt >= $endsAt) {
            return [];
        }

        return array_map('intval', array_keys($this->intervalLabelsByWorkspaceId($date, $startsAt, $endsAt)));
    }

    /**
     * @return array{status:string, label:string, description:string}
     */
    public function stateForWorkspace(Workspace $workspace, mixed $date, mixed $startsAt, mixed $endsAt): array
    {
        $date = $this->dateValue($date);
        $daySchedule = $this->scheduleForDate($date);
        $startsAt = $this->timeValue($startsAt, $daySchedule['starts_at']);
        $endsAt = $this->timeValue($endsAt, $this->defaultEndTime($date, $startsAt, $daySchedule));
        $currentLabels = $startsAt < $endsAt
            ? $this->intervalLabelsByWorkspaceId($date, $startsAt, $endsAt)
            : [];

        if (array_key_exists($workspace->id, $currentLabels)) {
            return [
                'status' => 'occupied',
                'label' => 'Занято',
                'description' => 'Занято в выбранное время: '.$currentLabels[$workspace->id],
            ];
        }

        $dailyLabels = $this->dailyLabelsByWorkspaceId($date);

        if (array_key_exists($workspace->id, $dailyLabels)) {
            return [
                'status' => 'partial',
                'label' => 'Свободно сейчас',
                'description' => 'Сегодня уже есть бронь: '.$dailyLabels[$workspace->id],
            ];
        }

        return [
            'status' => 'free',
            'label' => 'Свободно',
            'description' => 'На выбранное время броней нет.',
        ];
    }

    /**
     * @return Collection<int, array{time:string, user:string, status:string}>
     */
    public function scheduleForWorkspace(Workspace $workspace, mixed $date): Collection
    {
        $date = $this->dateValue($date);

        return WorkspaceBooking::query()
            ->with('user:id,name')
            ->whereDate('booking_date', $date)
            ->where('status', WorkspaceBookingStatus::Active)
            ->where(function ($query) use ($workspace): void {
                $query
                    ->where('workspace_id', $workspace->id)
                    ->orWhere(function ($query) use ($workspace): void {
                        $query
                            ->whereNull('workspace_id')
                            ->where('workspace_number', $workspace->number);
                    });
            })
            ->orderBy('starts_at')
            ->get()
            ->map(fn (WorkspaceBooking $booking): array => [
                'time' => $booking->starts_at->format('H:i').' - '.$booking->ends_at->format('H:i'),
                'user' => $booking->user?->name ?? 'Пользователь',
                'status' => $booking->status->label(),
            ]);
    }

    /**
     * @return array{starts_at:string, ends_at:string}|null
     */
    public function firstAvailableSlotForWorkspace(Workspace|int $workspace, mixed $date, ?string $preferredStart = null, int $minutes = self::DEFAULT_SLOT_MINUTES): ?array
    {
        $date = $this->dateValue($date);

        if (is_int($workspace)) {
            $workspace = Workspace::query()->find($workspace);
        }

        if (! $workspace instanceof Workspace) {
            return null;
        }

        return $this->firstAvailableSlotFromIntervals(
            $this->mergedIntervalsForWorkspace($workspace, $date),
            $date,
            $preferredStart,
            $minutes,
        );
    }

    /**
     * @param  Collection<int, array{starts_at:CarbonImmutable, ends_at:CarbonImmutable}>  $intervals
     * @param  array{starts_at:string, ends_at:string, label:string, setting:mixed}|null  $schedule
     * @return array{starts_at:string, ends_at:string}|null
     */
    private function firstAvailableSlotFromIntervals(Collection $intervals, string $date, ?string $preferredStart = null, int $minutes = self::DEFAULT_SLOT_MINUTES, ?array $schedule = null): ?array
    {
        $daySchedule = $schedule ?? $this->scheduleForDate($date);
        $dayStart = $this->dateTimeValue($date, $daySchedule['starts_at']);
        $dayEnd = $this->dateTimeValue($date, $daySchedule['ends_at']);
        $candidate = $this->dateTimeValue($date, $this->timeValue($preferredStart, $daySchedule['starts_at']));

        if ($candidate->lessThan($dayStart)) {
            $candidate = $dayStart;
        }

        foreach ($intervals as $interval) {
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
    public function startTimeOptionsForWorkspace(Workspace|int|null $workspace, mixed $date, int $minutes = self::DEFAULT_SLOT_MINUTES): array
    {
        $date = $this->dateValue($date);
        $daySchedule = $this->scheduleForDate($date);
        $dayStart = $this->dateTimeValue($date, $daySchedule['starts_at']);
        $dayEnd = $this->dateTimeValue($date, $daySchedule['ends_at']);
        $intervals = $this->intervalCollectionForWorkspace($workspace, $date);
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
    public function endTimeOptionsForWorkspace(Workspace|int|null $workspace, mixed $date, mixed $startsAt): array
    {
        $date = $this->dateValue($date);
        $daySchedule = $this->scheduleForDate($date);
        $startsAt = $this->timeValue($startsAt, $daySchedule['starts_at']);
        $start = $this->dateTimeValue($date, $startsAt);
        $dayStart = $this->dateTimeValue($date, $daySchedule['starts_at']);

        if ($start->lessThan($dayStart)) {
            $start = $dayStart;
        }

        $boundary = $this->nextBusyBoundary(
            $start,
            $this->dateTimeValue($date, $daySchedule['ends_at']),
            $this->intervalCollectionForWorkspace($workspace, $date),
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
    public function summaryForWorkspace(mixed $workspaceId, mixed $date): array
    {
        if (blank($workspaceId)) {
            return ['Выберите место, чтобы увидеть занятость.'];
        }

        $workspace = Workspace::query()
            ->with('assignedUser:id,name')
            ->find((int) $workspaceId);

        if (! $workspace instanceof Workspace) {
            return ['Рабочее место не найдено.'];
        }

        $date = $this->dateValue($date);
        $daySchedule = $this->scheduleForDate($date);
        $dailyLabels = $this->dailyLabelsByWorkspaceId($date);
        $slot = $this->firstAvailableSlotForWorkspace($workspace, $date);
        $lines = [];

        $lines[] = 'Рабочий день: '.$daySchedule['label'].'.';

        $lines[] = array_key_exists($workspace->id, $dailyLabels)
            ? 'Занято: '.$dailyLabels[$workspace->id]
            : 'На сегодня броней нет.';

        $lines[] = $slot
            ? 'Ближайший свободный час: '.$slot['starts_at'].' - '.$slot['ends_at'].'.'
            : 'Свободного часового окна до '.$daySchedule['ends_at'].' нет.';

        if ($workspace->assignedUser !== null) {
            $lines[] = 'Закреплено за: '.$workspace->assignedUser->name.'.';
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    public function dailyLabelsByWorkspaceId(mixed $date): array
    {
        return $this->labelsByWorkspaceId($this->dateValue($date));
    }

    /**
     * @return array<int, string>
     */
    private function intervalLabelsByWorkspaceId(string $date, string $startsAt, string $endsAt): array
    {
        return $this->labelsByWorkspaceId($date, $startsAt, $endsAt);
    }

    /**
     * @return array<int, string>
     */
    private function labelsByWorkspaceId(string $date, ?string $startsAt = null, ?string $endsAt = null): array
    {
        $workspaceIdsByNumber = Workspace::query()
            ->active()
            ->pluck('id', 'number');

        $bookings = WorkspaceBooking::query()
            ->whereDate('booking_date', $date)
            ->where('status', WorkspaceBookingStatus::Active)
            ->when($startsAt !== null && $endsAt !== null, function ($query) use ($startsAt, $endsAt): void {
                $query
                    ->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            })
            ->orderBy('starts_at')
            ->get(['workspace_id', 'workspace_number', 'starts_at', 'ends_at']);

        return $bookings
            ->groupBy(function (WorkspaceBooking $booking) use ($workspaceIdsByNumber): ?int {
                if ($booking->workspace_id !== null) {
                    return $booking->workspace_id;
                }

                $workspaceId = $workspaceIdsByNumber->get($booking->workspace_number);

                return $workspaceId === null ? null : (int) $workspaceId;
            })
            ->filter(fn (Collection $bookings, int|string $workspaceId): bool => filled($workspaceId))
            ->map(fn (Collection $bookings): string => $bookings
                ->map(fn (WorkspaceBooking $booking): string => $booking->starts_at->format('H:i').' - '.$booking->ends_at->format('H:i'))
                ->implode(', '))
            ->all();
    }

    /**
     * @return Collection<int, array{starts_at:CarbonImmutable, ends_at:CarbonImmutable}>
     */
    private function mergedIntervalsForWorkspace(Workspace $workspace, string $date): Collection
    {
        $merged = [];

        foreach ($this->intervalsForWorkspace($workspace, $date) as $interval) {
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
    private function intervalCollectionForWorkspace(Workspace|int|null $workspace, string $date): Collection
    {
        if (is_int($workspace)) {
            $workspace = Workspace::query()->find($workspace);
        }

        if (! $workspace instanceof Workspace) {
            return collect();
        }

        return $this->mergedIntervalsForWorkspace($workspace, $date);
    }

    /**
     * @return Collection<int, array{starts_at:CarbonImmutable, ends_at:CarbonImmutable}>
     */
    private function intervalsForWorkspace(Workspace $workspace, string $date): Collection
    {
        return WorkspaceBooking::query()
            ->whereDate('booking_date', $date)
            ->where('status', WorkspaceBookingStatus::Active)
            ->where(function ($query) use ($workspace): void {
                $query
                    ->where('workspace_id', $workspace->id)
                    ->orWhere(function ($query) use ($workspace): void {
                        $query
                            ->whereNull('workspace_id')
                            ->where('workspace_number', $workspace->number);
                    });
            })
            ->orderBy('starts_at')
            ->get(['starts_at', 'ends_at'])
            ->map(fn (WorkspaceBooking $booking): array => [
                'starts_at' => $this->dateTimeValue($date, $booking->starts_at->format('H:i')),
                'ends_at' => $this->dateTimeValue($date, $booking->ends_at->format('H:i')),
            ]);
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

    /**
     * @param  array{starts_at:string, ends_at:string, label:string, setting:mixed}  $schedule
     */
    private function defaultEndTime(string $date, string $startsAt, array $schedule): string
    {
        $defaultEnd = $this->dateTimeValue($date, $startsAt)->addMinutes(self::DEFAULT_SLOT_MINUTES);
        $dayEnd = $this->dateTimeValue($date, $schedule['ends_at']);

        return ($defaultEnd->greaterThan($dayEnd) ? $dayEnd : $defaultEnd)->format('H:i');
    }

    /**
     * @param  array{starts_at:string, ends_at:string, label:string, setting:mixed}  $schedule
     */
    private function bookingCoversSchedule(WorkspaceBooking $booking, string $date, array $schedule): bool
    {
        $bookingStart = $this->dateTimeValue($date, $booking->starts_at->format('H:i'));
        $bookingEnd = $this->dateTimeValue($date, $booking->ends_at->format('H:i'));
        $dayStart = $this->dateTimeValue($date, $schedule['starts_at']);
        $dayEnd = $this->dateTimeValue($date, $schedule['ends_at']);

        return $bookingStart->lessThanOrEqualTo($dayStart) && $bookingEnd->greaterThanOrEqualTo($dayEnd);
    }

    /**
     * @param  Collection<int, array{starts_at:CarbonImmutable, ends_at:CarbonImmutable}>  $intervals
     * @param  array{starts_at:string, ends_at:string, label:string, setting:mixed}  $schedule
     */
    private function scheduleIsCovered(Collection $intervals, string $date, array $schedule): bool
    {
        $cursor = $this->dateTimeValue($date, $schedule['starts_at']);
        $dayEnd = $this->dateTimeValue($date, $schedule['ends_at']);

        foreach ($intervals->sortBy('starts_at') as $interval) {
            if ($interval['ends_at']->lessThanOrEqualTo($cursor)) {
                continue;
            }

            if ($interval['starts_at']->greaterThan($cursor)) {
                return false;
            }

            $cursor = $interval['ends_at'];

            if ($cursor->greaterThanOrEqualTo($dayEnd)) {
                return true;
            }
        }

        return false;
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
