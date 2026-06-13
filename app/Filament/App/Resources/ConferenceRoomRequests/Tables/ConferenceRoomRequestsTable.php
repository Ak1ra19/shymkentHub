<?php

namespace App\Filament\App\Resources\ConferenceRoomRequests\Tables;

use App\Enums\ConferenceRoomRequestStatus;
use App\Models\ConferenceRoomRequest;
use App\Services\ConferenceRoomRequestService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConferenceRoomRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('booking_date', 'desc')
            ->columns([
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
                TextColumn::make('purpose')
                    ->label('Цель')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (ConferenceRoomRequestStatus $state): string => match ($state) {
                        ConferenceRoomRequestStatus::Pending => 'warning',
                        ConferenceRoomRequestStatus::Approved => 'success',
                        ConferenceRoomRequestStatus::Rejected, ConferenceRoomRequestStatus::Cancelled => 'danger',
                        ConferenceRoomRequestStatus::Completed => 'gray',
                    })
                    ->formatStateUsing(fn (ConferenceRoomRequestStatus $state): string => $state->label()),
                TextColumn::make('admin_comment')
                    ->label('Комментарий администратора')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(ConferenceRoomRequestStatus::cases())->mapWithKeys(fn (ConferenceRoomRequestStatus $status): array => [$status->value => $status->label()])->all()),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Отменить')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ConferenceRoomRequest $record): bool => in_array($record->status, [
                        ConferenceRoomRequestStatus::Pending,
                        ConferenceRoomRequestStatus::Approved,
                    ], true))
                    ->action(fn (ConferenceRoomRequest $record): ConferenceRoomRequest => app(ConferenceRoomRequestService::class)->cancel($record, auth()->user())),
            ]);
    }
}
