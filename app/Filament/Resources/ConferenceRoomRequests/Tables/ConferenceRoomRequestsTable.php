<?php

namespace App\Filament\Resources\ConferenceRoomRequests\Tables;

use App\Enums\ConferenceRoomRequestStatus;
use App\Models\ConferenceRoomRequest;
use App\Services\ConferenceRoomRequestService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConferenceRoomRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('ФИО')
                    ->searchable(),
                TextColumn::make('user.company')
                    ->label('Компания')
                    ->limit(24)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
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
                    ->limit(36)
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
                    ->label('Комментарий')
                    ->placeholder('-')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(ConferenceRoomRequestStatus::cases())->mapWithKeys(fn (ConferenceRoomRequestStatus $status): array => [$status->value => $status->label()])->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Одобрить')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn (ConferenceRoomRequest $record): bool => $record->status === ConferenceRoomRequestStatus::Pending)
                        ->action(fn (ConferenceRoomRequest $record): ConferenceRoomRequest => app(ConferenceRoomRequestService::class)->approve($record, auth()->user())),
                    Action::make('reject')
                        ->label('Отклонить')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->visible(fn (ConferenceRoomRequest $record): bool => $record->status === ConferenceRoomRequestStatus::Pending)
                        ->schema([
                            Textarea::make('admin_comment')
                                ->label('Комментарий')
                                ->required(),
                        ])
                        ->action(fn (ConferenceRoomRequest $record, array $data): ConferenceRoomRequest => app(ConferenceRoomRequestService::class)->reject($record, auth()->user(), $data['admin_comment'] ?? null)),
                    Action::make('cancel')
                        ->label('Отменить')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn (ConferenceRoomRequest $record): bool => in_array($record->status, [
                            ConferenceRoomRequestStatus::Pending,
                            ConferenceRoomRequestStatus::Approved,
                        ], true))
                        ->action(fn (ConferenceRoomRequest $record): ConferenceRoomRequest => app(ConferenceRoomRequestService::class)->cancel($record, auth()->user())),
                ])
                    ->label('Действия')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->button()
                    ->visible(fn (ConferenceRoomRequest $record): bool => in_array($record->status, [
                        ConferenceRoomRequestStatus::Pending,
                        ConferenceRoomRequestStatus::Approved,
                    ], true)),
            ]);
    }
}
