<?php

namespace App\Enums;

enum WorkspaceBookingStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Активно',
            self::Cancelled => 'Отменено',
            self::Completed => 'Завершено',
        };
    }
}
