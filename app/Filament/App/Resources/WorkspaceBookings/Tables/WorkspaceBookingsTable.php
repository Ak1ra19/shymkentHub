<?php

namespace App\Filament\App\Resources\WorkspaceBookings\Tables;

use App\Enums\WorkspaceBookingStatus;
use App\Models\WorkspaceBooking;
use App\Services\WorkspaceBookingService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkspaceBookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('booking_date', 'desc')
            ->columns([
                TextColumn::make('workspace_number')
                    ->label('Место')
                    ->state(fn (WorkspaceBooking $record): string => $record->workspace?->displayName() ?? '№ '.$record->workspace_number)
                    ->sortable(),
                TextColumn::make('booking_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Начало')
                    ->time('H:i'),
                TextColumn::make('ends_at')
                    ->label('Окончание')
                    ->time('H:i'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (WorkspaceBookingStatus $state): string => match ($state) {
                        WorkspaceBookingStatus::Active => 'success',
                        WorkspaceBookingStatus::Cancelled => 'danger',
                        WorkspaceBookingStatus::Completed => 'gray',
                    })
                    ->formatStateUsing(fn (WorkspaceBookingStatus $state): string => $state->label()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(WorkspaceBookingStatus::cases())->mapWithKeys(fn (WorkspaceBookingStatus $status): array => [$status->value => $status->label()])->all()),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Отменить')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (WorkspaceBooking $record): bool => $record->status === WorkspaceBookingStatus::Active)
                    ->action(fn (WorkspaceBooking $record): WorkspaceBooking => app(WorkspaceBookingService::class)->cancel($record, auth()->user())),
            ]);
    }
}
