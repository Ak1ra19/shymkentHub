<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Описание')
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('event_date')
                    ->label('Дата')
                    ->required(),
                TimePicker::make('event_time')
                    ->label('Время')
                    ->seconds(false)
                    ->required(),
                FileUpload::make('banner_path')
                    ->label('Баннер')
                    ->image()
                    ->directory('events')
                    ->columnSpanFull(),
            ]);
    }
}
