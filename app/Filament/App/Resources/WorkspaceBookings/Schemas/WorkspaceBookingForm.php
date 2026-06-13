<?php

namespace App\Filament\App\Resources\WorkspaceBookings\Schemas;

use App\Services\WorkspaceAvailability;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class WorkspaceBookingForm
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
                                    ->maxDate(now()->endOfDay())
                                    ->live()
                                    ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::fillNearestWorkspaceSlot($get('workspace_id'), $state, $set, $get('starts_at')))
                                    ->required(),
                                Select::make('starts_at')
                                    ->label('Начало')
                                    ->options(fn (Get $get): array => app(WorkspaceAvailability::class)->startTimeOptionsForWorkspace(
                                        $get('workspace_id'),
                                        $get('booking_date'),
                                    ))
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::syncWorkspaceEndTime(
                                        $get('workspace_id'),
                                        $get('booking_date'),
                                        $state,
                                        $get('ends_at'),
                                        $set,
                                    ))
                                    ->required(),
                                Select::make('ends_at')
                                    ->label('Окончание')
                                    ->options(fn (Get $get): array => app(WorkspaceAvailability::class)->endTimeOptionsForWorkspace(
                                        $get('workspace_id'),
                                        $get('booking_date'),
                                        $get('starts_at'),
                                    ))
                                    ->native(false)
                                    ->nullable(),
                            ]),
                    ]),
                Section::make('Рабочее место')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('workspace_id')
                            ->label('Место')
                            ->options(fn (Get $get): array => app(WorkspaceAvailability::class)->optionsForSelection(
                                $get('booking_date'),
                                $get('starts_at'),
                                $get('ends_at'),
                                auth()->user(),
                            ))
                            ->default(fn (): ?int => request()->integer('workspace_id') ?: null)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateHydrated(fn (mixed $state, Get $get, Set $set): mixed => self::fillNearestWorkspaceSlot($state, $get('booking_date'), $set, $get('starts_at')))
                            ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::fillNearestWorkspaceSlot($state, $get('booking_date'), $set, $get('starts_at')))
                            ->helperText('После выбора места время автоматически ставится на ближайший свободный час.')
                            ->required(),
                        Placeholder::make('workspace_schedule')
                            ->label('Занятость')
                            ->content(fn (Get $get): array => app(WorkspaceAvailability::class)->summaryForWorkspace(
                                $get('workspace_id'),
                                $get('booking_date'),
                            ))
                            ->bulleted(),
                    ]),
            ]);
    }

    private static function fillNearestWorkspaceSlot(mixed $workspaceId, mixed $date, Set $set, mixed $preferredStart = null): void
    {
        if (blank($workspaceId)) {
            self::syncWorkspaceEndTime($workspaceId, $date, $preferredStart, null, $set);

            return;
        }

        $slot = app(WorkspaceAvailability::class)->firstAvailableSlotForWorkspace(
            (int) $workspaceId,
            $date,
            self::timeValue($preferredStart),
        );

        if (! $slot) {
            return;
        }

        $set('starts_at', $slot['starts_at']);
        $set('ends_at', $slot['ends_at']);
    }

    private static function syncWorkspaceEndTime(mixed $workspaceId, mixed $date, mixed $startsAt, mixed $currentEndsAt, Set $set): void
    {
        $startsAt = self::timeValue($startsAt);

        if ($startsAt === null) {
            $set('ends_at', null);

            return;
        }

        $options = app(WorkspaceAvailability::class)->endTimeOptionsForWorkspace($workspaceId, $date, $startsAt);

        if ($options === []) {
            $set('ends_at', null);

            return;
        }

        $currentEndsAt = self::timeValue($currentEndsAt);

        if ($currentEndsAt !== null && array_key_exists($currentEndsAt, $options)) {
            return;
        }

        $defaultEnd = CarbonImmutable::createFromFormat('H:i', $startsAt)->addHour()->format('H:i');
        $slotEnd = filled($workspaceId)
            ? app(WorkspaceAvailability::class)->firstAvailableSlotForWorkspace((int) $workspaceId, $date, $startsAt)['ends_at'] ?? null
            : null;

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
