<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Событие')
                    ->limit(36)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label('Пользователь')
                    ->limit(28)
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Объект')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', now()->toDateString())),
            ])
            ->recordActions([]);
    }
}
