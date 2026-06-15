<?php

namespace App\Filament\Resources\WorkspaceScheduleSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkspaceScheduleSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_on')
                    ->label('Действует с')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Начало')
                    ->formatStateUsing(fn (mixed $state): string => $state?->format('H:i') ?? '-')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Конец')
                    ->formatStateUsing(fn (mixed $state): string => $state?->format('H:i') ?? '-')
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Комментарий')
                    ->placeholder('Без комментария')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('starts_on', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать')
                    ->icon(Heroicon::OutlinedPencilSquare),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }
}
