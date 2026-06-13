<?php

namespace App\Services;

use App\Enums\ConferenceRoomRequestStatus;
use App\Filament\App\Resources\ConferenceRoomRequests\ConferenceRoomRequestResource;
use App\Models\ConferenceRoomRequest;
use App\Models\User;
use App\Notifications\SystemNotification;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConferenceRoomRequestService
{
    private const DAILY_LIMIT_MINUTES = 120;

    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array{booking_date:string, starts_at:mixed, ends_at:mixed, purpose:string}  $data
     */
    public function create(User $user, array $data): ConferenceRoomRequest
    {
        $bookingDate = CarbonImmutable::parse($data['booking_date'])->toDateString();
        $startsAt = $this->timeValue($data['starts_at'] ?? null, 'starts_at');
        $endsAt = filled($data['ends_at'] ?? null)
            ? $this->timeValue($data['ends_at'], 'ends_at')
            : $this->oneHourAfter($startsAt);

        if ($endsAt <= $startsAt) {
            throw ValidationException::withMessages([
                'ends_at' => 'Время окончания должно быть позже времени начала.',
            ]);
        }

        $requestedMinutes = $this->minutesBetween($startsAt, $endsAt);

        return DB::transaction(function () use ($user, $data, $bookingDate, $startsAt, $endsAt, $requestedMinutes): ConferenceRoomRequest {
            $activeStatuses = [
                ConferenceRoomRequestStatus::Pending,
                ConferenceRoomRequestStatus::Approved,
            ];

            $hasConflict = ConferenceRoomRequest::query()
                ->whereDate('booking_date', $bookingDate)
                ->whereIn('status', $activeStatuses)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Конференц-зал уже занят на выбранное время.',
                ]);
            }

            $usedMinutes = ConferenceRoomRequest::query()
                ->whereBelongsTo($user)
                ->whereDate('booking_date', $bookingDate)
                ->whereIn('status', $activeStatuses)
                ->lockForUpdate()
                ->get(['starts_at', 'ends_at'])
                ->sum(fn (ConferenceRoomRequest $request): int => $this->minutesBetween(
                    $request->starts_at->format('H:i'),
                    $request->ends_at->format('H:i'),
                ));

            if ($usedMinutes + $requestedMinutes > self::DAILY_LIMIT_MINUTES) {
                throw ValidationException::withMessages([
                    'ends_at' => 'Лимит бронирования конференц-зала — 2 часа в день.',
                ]);
            }

            $conferenceRequest = ConferenceRoomRequest::create([
                'user_id' => $user->id,
                'booking_date' => $bookingDate,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'purpose' => $data['purpose'],
                'status' => ConferenceRoomRequestStatus::Pending,
            ]);

            $user->notify(new SystemNotification(
                title: 'Заявка отправлена',
                body: 'Заявка на конференц-зал отправлена администратору.',
                data: ['request_id' => $conferenceRequest->id],
                url: ConferenceRoomRequestResource::getUrl('index', panel: 'app'),
            ));

            $this->activityLogger->log('conference_room_request.created', $user, $conferenceRequest, [
                'booking_date' => $conferenceRequest->booking_date->toDateString(),
                'purpose' => $conferenceRequest->purpose,
            ]);

            return $conferenceRequest;
        });
    }

    public function approve(ConferenceRoomRequest $request, User $admin): ConferenceRoomRequest
    {
        $request->update([
            'status' => ConferenceRoomRequestStatus::Approved,
            'reviewed_by_id' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $request->user->notify(new SystemNotification(
            title: 'Заявка одобрена',
            body: 'Конференц-зал подтвержден на '.$request->booking_date->format('d.m.Y').' с '.$request->starts_at->format('H:i').' до '.$request->ends_at->format('H:i').'.',
            data: ['request_id' => $request->id],
            url: ConferenceRoomRequestResource::getUrl('index', panel: 'app'),
        ));

        $this->activityLogger->log('conference_room_request.approved', $admin, $request);

        return $request;
    }

    public function reject(ConferenceRoomRequest $request, User $admin, ?string $comment): ConferenceRoomRequest
    {
        $request->update([
            'status' => ConferenceRoomRequestStatus::Rejected,
            'admin_comment' => $comment,
            'reviewed_by_id' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $request->user->notify(new SystemNotification(
            title: 'Заявка отклонена',
            body: $comment ?: 'Администратор отклонил заявку на конференц-зал.',
            data: ['request_id' => $request->id],
            url: ConferenceRoomRequestResource::getUrl('index', panel: 'app'),
        ));

        $this->activityLogger->log('conference_room_request.rejected', $admin, $request, [
            'admin_comment' => $comment,
        ]);

        return $request;
    }

    public function cancel(ConferenceRoomRequest $request, ?User $actor = null): ConferenceRoomRequest
    {
        $request->update([
            'status' => ConferenceRoomRequestStatus::Cancelled,
        ]);

        $request->user->notify(new SystemNotification(
            title: 'Заявка отменена',
            body: 'Заявка на конференц-зал отменена.',
            data: ['request_id' => $request->id],
            url: ConferenceRoomRequestResource::getUrl('index', panel: 'app'),
        ));

        $this->activityLogger->log('conference_room_request.cancelled', $actor ?? $request->user, $request);

        return $request;
    }

    private function minutesBetween(string $startsAt, string $endsAt): int
    {
        $start = CarbonImmutable::createFromFormat('H:i', $startsAt);
        $end = CarbonImmutable::createFromFormat('H:i', $endsAt);

        return (int) $start->diffInMinutes($end);
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
