<?php

namespace App\Enums;

enum ConferenceRoomRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'На рассмотрении',
            self::Approved => 'Одобрено',
            self::Rejected => 'Отклонено',
            self::Cancelled => 'Отменено',
            self::Completed => 'Завершено',
        };
    }
}
