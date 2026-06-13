<?php

namespace App\Filament\Resources\Workspaces\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkspaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Место')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                TextInput::make('number')
                                    ->label('Номер')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(255)
                                    ->unique(ignoreRecord: true)
                                    ->required(),
                                TextInput::make('zone')
                                    ->label('Зона')
                                    ->default('Общий зал')
                                    ->maxLength(255),
                                TextInput::make('label')
                                    ->label('Название')
                                    ->maxLength(255),
                                TextInput::make('sort_order')
                                    ->label('Порядок')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Доступ')
                    ->schema([
                        Select::make('assigned_user_id')
                            ->label('Закреплено за')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Если выбрать пользователя, место будет доступно для бронирования только ему.'),
                        Toggle::make('is_active')
                            ->label('Активно')
                            ->default(true),
                    ]),
            ]);
    }
}
