<?php

namespace App\Services;

use App\Enums\WorkspaceBookingStatus;
use App\Filament\App\Resources\WorkspaceBookings\WorkspaceBookingResource;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBooking;
use App\Notifications\SystemNotification;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkspaceBookingService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array{workspace_id?:int|string|null, workspace_number?:int|string|null, booking_date:string, starts_at:mixed, ends_at:mixed}  $data
     */
    public function create(User $user, array $data): WorkspaceBooking
    {
        $bookingDate = CarbonImmutable::parse($data['booking_date'])->toDateString();
        $startsAt = $this->timeValue($data['starts_at'] ?? null, 'starts_at');
        $endsAt = filled($data['ends_at'] ?? null)
            ? $this->timeValue($data['ends_at'], 'ends_at')
            : $this->oneHourAfter($startsAt);

        if ($bookingDate !== now()->toDateString()) {
            throw ValidationException::withMessages([
                'booking_date' => 'Рабочее место можно бронировать только на текущий день.',
            ]);
        }

        if ($endsAt <= $startsAt) {
            throw ValidationException::withMessages([
                'ends_at' => 'Время окончания должно быть позже времени начала.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $bookingDate, $startsAt, $endsAt): WorkspaceBooking {
            $workspace = $this->resolveWorkspace($user, $data);

            $hasConflict = WorkspaceBooking::query()
                ->where(function ($query) use ($workspace): void {
                    $query
                        ->where('workspace_id', $workspace->id)
                        ->orWhere(function ($query) use ($workspace): void {
                            $query
                                ->whereNull('workspace_id')
                                ->where('workspace_number', $workspace->number);
                        });
                })
                ->whereDate('booking_date', $bookingDate)
                ->where('status', WorkspaceBookingStatus::Active)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'workspace_id' => 'Это рабочее место уже занято на выбранное время.',
                ]);
            }

            $booking = WorkspaceBooking::create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
                'workspace_number' => $workspace->number,
                'booking_date' => $bookingDate,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => WorkspaceBookingStatus::Active,
            ]);

            $user->notify(new SystemNotification(
                title: 'Рабочее место забронировано',
                body: 'Место № '.$booking->workspace_number.' доступно '.$booking->booking_date->format('d.m.Y').' с '.$booking->starts_at->format('H:i').' до '.$booking->ends_at->format('H:i').'.',
                data: ['booking_id' => $booking->id],
                url: WorkspaceBookingResource::getUrl('index', panel: 'app'),
            ));

            $this->activityLogger->log('workspace_booking.created', $user, $booking, [
                'workspace_number' => $booking->workspace_number,
                'booking_date' => $booking->booking_date->toDateString(),
            ]);

            return $booking;
        });
    }

    public function cancel(WorkspaceBooking $booking, ?User $actor = null): WorkspaceBooking
    {
        $booking->update([
            'status' => WorkspaceBookingStatus::Cancelled,
        ]);

        $booking->user->notify(new SystemNotification(
            title: 'Бронирование отменено',
            body: 'Бронирование рабочего места № '.$booking->workspace_number.' отменено.',
            data: ['booking_id' => $booking->id],
            url: WorkspaceBookingResource::getUrl('index', panel: 'app'),
        ));

        $this->activityLogger->log('workspace_booking.cancelled', $actor ?? $booking->user, $booking, [
            'workspace_number' => $booking->workspace_number,
        ]);

        return $booking;
    }

    /**
     * @param  array{workspace_id?:int|string|null, workspace_number?:int|string|null}  $data
     */
    private function resolveWorkspace(User $user, array $data): Workspace
    {
        $workspace = Workspace::query()
            ->active()
            ->where(function ($query) use ($user): void {
                $query
                    ->whereNull('assigned_user_id')
                    ->orWhere('assigned_user_id', $user->id);
            })
            ->when(filled($data['workspace_id'] ?? null), function ($query) use ($data): void {
                $query->whereKey((int) $data['workspace_id']);
            }, function ($query) use ($data): void {
                $query->where('number', (int) ($data['workspace_number'] ?? 0));
            })
            ->first();

        if (! $workspace) {
            throw ValidationException::withMessages([
                'workspace_id' => 'Выберите доступное рабочее место.',
            ]);
        }

        return $workspace;
    }

    private function timeValue(mixed $value, string $field): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        preg_match('/\d{2}:\d{2}/', (string) $value, $matches);

        if (isset($matches[0])) {
            return $matches[0];
        }

        throw ValidationException::withMessages([
            $field => 'Укажите время в формате 09:00.',
        ]);
    }

    private function oneHourAfter(string $startsAt): string
    {
        return CarbonImmutable::createFromFormat('H:i', $startsAt)->addHour()->format('H:i');
    }
}
