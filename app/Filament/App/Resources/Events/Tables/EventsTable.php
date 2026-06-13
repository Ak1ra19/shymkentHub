<?php

namespace App\Filament\App\Resources\Events\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('event_date')
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Описание')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('event_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('event_time')
                    ->label('Время')
                    ->time('H:i'),
            ])
            ->recordActions([]);
    }
}
