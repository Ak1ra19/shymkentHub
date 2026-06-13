<?php

namespace App\Filament\Resources\Workspaces\Tables;

use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkspacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Номер')
                    ->formatStateUsing(fn (int $state): string => '№ '.$state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Название')
                    ->placeholder('Без названия')
                    ->limit(24)
                    ->searchable(),
                TextColumn::make('zone')
                    ->label('Зона')
                    ->placeholder('Не указана')
                    ->limit(24)
                    ->searchable(),
                TextColumn::make('assignedUser.name')
                    ->label('Закреплено за')
                    ->placeholder('Общее')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean(),
                TextColumn::make('bookings_count')
                    ->label('Брони')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        '1' => 'Активные',
                        '0' => 'Отключенные',
                    ]),
                SelectFilter::make('assigned_user_id')
                    ->label('Закреплено за')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Редактировать')
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('deactivate')
                        ->label('Отключить')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Workspace $record): bool => $record->is_active)
                        ->action(fn (Workspace $record): bool => $record->update(['is_active' => false])),
                    Action::make('activate')
                        ->label('Включить')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Workspace $record): bool => ! $record->is_active)
                        ->action(fn (Workspace $record): bool => $record->update(['is_active' => true])),
                    DeleteAction::make()
                        ->label('Удалить')
                        ->icon(Heroicon::OutlinedTrash)
                        ->visible(fn (Workspace $record): bool => (int) $record->bookings_count === 0),
                ])
                    ->label('Действия')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([]);
    }
}
