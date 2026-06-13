<?php

namespace App\Filament\App\Resources\ConferenceRoomRequests\Schemas;

use App\Services\ConferenceRoomAvailability;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ConferenceRoomRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Время')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                DatePicker::make('booking_date')
                                    ->label('Дата')
                                    ->default(now()->toDateString())
                                    ->native(false)
                                    ->displayFormat('d.m.Y')
                                    ->minDate(now()->startOfDay())
                                    ->live()
                                    ->afterStateHydrated(fn (mixed $state, Get $get, Set $set): mixed => self::fillNearestConferenceSlot($state, $set, $get('starts_at')))
                                    ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::fillNearestConferenceSlot($state, $set, $get('starts_at')))
                                    ->required(),
                                Select::make('starts_at')
                                    ->label('Начало')
                                    ->default(fn (): string => self::defaultConferenceSlot('starts_at'))
                                    ->options(fn (Get $get): array => app(ConferenceRoomAvailability::class)->startTimeOptions($get('booking_date')))
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::syncConferenceEndTime(
                                        $get('booking_date'),
                                        $state,
                                        $get('ends_at'),
                                        $set,
                                    ))
                                    ->required(),
                                Select::make('ends_at')
                                    ->label('Окончание')
                                    ->default(fn (): string => self::defaultConferenceSlot('ends_at'))
                                    ->options(fn (Get $get): array => app(ConferenceRoomAvailability::class)->endTimeOptions(
                                        $get('booking_date'),
                                        $get('starts_at'),
                                    ))
                                    ->native(false)
                                    ->nullable(),
                            ]),
                        Placeholder::make('conference_room_schedule')
                            ->label('Занятость зала')
                            ->content(fn (Get $get): array => app(ConferenceRoomAvailability::class)->summary($get('booking_date')))
                            ->bulleted(),
                    ]),
                Section::make('Заявка')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('purpose')
                            ->label('Цель бронирования')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('admin_comment')
                            ->label('Комментарий администратора')
                            ->disabled()
                            ->visible(fn (?string $state): bool => filled($state)),
                    ]),
            ]);
    }

    private static function fillNearestConferenceSlot(mixed $date, Set $set, mixed $preferredStart = null): void
    {
        $slot = app(ConferenceRoomAvailability::class)->firstAvailableSlot($date, self::timeValue($preferredStart));

        if (! $slot) {
            return;
        }

        $set('starts_at', $slot['starts_at']);
        $set('ends_at', $slot['ends_at']);
    }

    private static function defaultConferenceSlot(string $key): string
    {
        $slot = app(ConferenceRoomAvailability::class)->firstAvailableSlot(now()->toDateString());

        return $slot[$key] ?? ($key === 'starts_at' ? '09:00' : '10:00');
    }

    private static function syncConferenceEndTime(mixed $date, mixed $startsAt, mixed $currentEndsAt, Set $set): void
    {
        $startsAt = self::timeValue($startsAt);

        if ($startsAt === null) {
            $set('ends_at', null);

            return;
        }

        $options = app(ConferenceRoomAvailability::class)->endTimeOptions($date, $startsAt);

        if ($options === []) {
            $set('ends_at', null);

            return;
        }

        $currentEndsAt = self::timeValue($currentEndsAt);

        if ($currentEndsAt !== null && array_key_exists($currentEndsAt, $options)) {
            return;
        }

        $defaultEnd = CarbonImmutable::createFromFormat('H:i', $startsAt)->addHour()->format('H:i');
        $slotEnd = app(ConferenceRoomAvailability::class)->firstAvailableSlot($date, $startsAt)['ends_at'] ?? null;

        if (! array_key_exists($defaultEnd, $options)) {
            $defaultEnd = $slotEnd;
        }

        $set('ends_at', $defaultEnd && array_key_exists($defaultEnd, $options) ? $defaultEnd : array_key_first($options));
    }

    private static function timeValue(mixed $value): ?string
    {
        preg_match('/\d{2}:\d{2}/', (string) $value, $matches);

        return $matches[0] ?? null;
    }
}
