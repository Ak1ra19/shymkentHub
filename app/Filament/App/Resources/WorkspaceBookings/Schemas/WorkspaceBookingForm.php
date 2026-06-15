<?php

namespace App\Filament\App\Resources\WorkspaceBookings\Schemas;

use App\Models\Workspace;
use App\Services\WorkspaceAvailability;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class WorkspaceBookingForm
{
    public static function configure(Schema $schema, bool $workspaceIsSelectable = true): Schema
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
                                    ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::fillWorkspaceSlot($get('workspace_id'), $state, $set, $get('starts_at'), (bool) $get('full_day')))
                                    ->required(),
                                Select::make('starts_at')
                                    ->label('Начало')
                                    ->options(fn (Get $get): array => app(WorkspaceAvailability::class)->startTimeOptionsForWorkspace(
                                        $get('workspace_id'),
                                        $get('booking_date'),
                                    ))
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->disabled(fn (Get $get): bool => (bool) $get('full_day'))
                                    ->dehydrated()
                                    ->live()
                                    ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                                        if ((bool) $get('full_day')) {
                                            self::fillFullDayWorkspaceSlot($get('booking_date'), $set);

                                            return;
                                        }

                                        self::syncWorkspaceEndTime(
                                            $get('workspace_id'),
                                            $get('booking_date'),
                                            $state,
                                            $get('ends_at'),
                                            $set,
                                        );
                                    })
                                    ->required(),
                                Select::make('ends_at')
                                    ->label('Окончание')
                                    ->options(fn (Get $get): array => app(WorkspaceAvailability::class)->endTimeOptionsForWorkspace(
                                        $get('workspace_id'),
                                        $get('booking_date'),
                                        $get('starts_at'),
                                    ))
                                    ->native(false)
                                    ->disabled(fn (Get $get): bool => (bool) $get('full_day'))
                                    ->dehydrated()
                                    ->nullable(),
                            ]),
                        Toggle::make('full_day')
                            ->label('На весь рабочий день')
                            ->helperText('Место будет забронировано с начала до конца действующего режима работы зала.')
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::fillWorkspaceSlot($get('workspace_id'), $get('booking_date'), $set, $get('starts_at'), (bool) $state)),
                    ]),
                Section::make('Рабочее место')
                    ->columnSpanFull()
                    ->schema(self::workspaceComponents($workspaceIsSelectable)),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function workspaceComponents(bool $workspaceIsSelectable): array
    {
        if (! $workspaceIsSelectable) {
            return [
                Hidden::make('workspace_id')
                    ->live()
                    ->afterStateHydrated(fn (mixed $state, Get $get, Set $set): mixed => self::fillWorkspaceSlot($state, $get('booking_date'), $set, $get('starts_at'), (bool) $get('full_day')))
                    ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::fillWorkspaceSlot($state, $get('booking_date'), $set, $get('starts_at'), (bool) $get('full_day')))
                    ->required(),
                Placeholder::make('selected_workspace')
                    ->label('Выбранное место')
                    ->content(fn (Get $get): string => self::workspaceLabel($get('workspace_id'))),
                Placeholder::make('workspace_schedule')
                    ->label('Занятость')
                    ->content(fn (Get $get): array => app(WorkspaceAvailability::class)->summaryForWorkspace(
                        $get('workspace_id'),
                        $get('booking_date'),
                    ))
                    ->bulleted(),
            ];
        }

        return [
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
                ->afterStateHydrated(fn (mixed $state, Get $get, Set $set): mixed => self::fillWorkspaceSlot($state, $get('booking_date'), $set, $get('starts_at'), (bool) $get('full_day')))
                ->afterStateUpdated(fn (mixed $state, Get $get, Set $set): mixed => self::fillWorkspaceSlot($state, $get('booking_date'), $set, $get('starts_at'), (bool) $get('full_day')))
                ->helperText('После выбора места время автоматически ставится на ближайший свободный час.')
                ->required(),
            Placeholder::make('workspace_schedule')
                ->label('Занятость')
                ->content(fn (Get $get): array => app(WorkspaceAvailability::class)->summaryForWorkspace(
                    $get('workspace_id'),
                    $get('booking_date'),
                ))
                ->bulleted(),
        ];
    }

    private static function fillWorkspaceSlot(mixed $workspaceId, mixed $date, Set $set, mixed $preferredStart = null, bool $fullDay = false): void
    {
        if ($fullDay) {
            self::fillFullDayWorkspaceSlot($date, $set);

            return;
        }

        self::fillNearestWorkspaceSlot($workspaceId, $date, $set, $preferredStart);
    }

    private static function fillFullDayWorkspaceSlot(mixed $date, Set $set): void
    {
        $slot = app(WorkspaceAvailability::class)->fullDaySlot($date);

        $set('starts_at', $slot['starts_at']);
        $set('ends_at', $slot['ends_at']);
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

    private static function workspaceLabel(mixed $workspaceId): string
    {
        if (blank($workspaceId)) {
            return 'Место не выбрано.';
        }

        $workspace = Workspace::query()->find((int) $workspaceId);

        return $workspace instanceof Workspace
            ? $workspace->displayName()
            : 'Рабочее место не найдено.';
    }
}
