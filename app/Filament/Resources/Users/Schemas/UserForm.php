<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('ФИО')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('position')
                    ->label('Должность')
                    ->required(),
                TextInput::make('company')
                    ->label('Компания')
                    ->required(),
                Select::make('role')
                    ->label('Роль')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])->all())
                    ->required(),
                Toggle::make('is_blocked')
                    ->label('Заблокирован'),
                DateTimePicker::make('email_verified_at')
                    ->label('Email подтвержден'),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
            ]);
    }
}
