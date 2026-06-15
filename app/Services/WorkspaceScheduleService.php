<?php

namespace App\Services;

use App\Models\WorkspaceScheduleSetting;
use Carbon\CarbonImmutable;
use DateTimeInterface;

class WorkspaceScheduleService
{
    public const DEFAULT_START = '09:00';

    public const DEFAULT_END = '18:00';

    /**
     * @return array{starts_at:string, ends_at:string, label:string, setting:WorkspaceScheduleSetting|null}
     */
    public function forDate(mixed $date): array
    {
        $date = $this->dateValue($date);

        $setting = WorkspaceScheduleSetting::query()
            ->where('starts_on', '<=', $date)
            ->latest('starts_on')
            ->first();

        $startsAt = $this->timeValue($setting?->starts_at, self::DEFAULT_START);
        $endsAt = $this->timeValue($setting?->ends_at, self::DEFAULT_END);

        return [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'label' => $startsAt.' - '.$endsAt,
            'setting' => $setting,
        ];
    }

    /**
     * @return array{starts_at:string, ends_at:string}
     */
    public function fullDaySlot(mixed $date): array
    {
        $schedule = $this->forDate($date);

        return [
            'starts_at' => $schedule['starts_at'],
            'ends_at' => $schedule['ends_at'],
        ];
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

        preg_match('/\d{2}:\d{2}/', (string) $value, $matches);

        return $matches[0] ?? $fallback;
    }
}
