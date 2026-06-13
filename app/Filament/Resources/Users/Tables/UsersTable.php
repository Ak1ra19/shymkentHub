<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('ФИО')
                    ->limit(28)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('company')
                    ->label('Компания')
                    ->limit(24)
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label()),
                IconColumn::make('is_blocked')
                    ->label('Блокировка')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Обновлен')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Роль')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Редактировать')
                        ->icon(Heroicon::OutlinedPencilSquare),
                    Action::make('block')
                        ->label('Заблокировать')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => ! $record->is_blocked)
                        ->action(function (User $record): void {
                            $record->update(['is_blocked' => true]);
                            app(ActivityLogger::class)->log('user.blocked', auth()->user(), $record);
                        }),
                    Action::make('unblock')
                        ->label('Разблокировать')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => $record->is_blocked)
                        ->action(function (User $record): void {
                            $record->update(['is_blocked' => false]);
                            app(ActivityLogger::class)->log('user.unblocked', auth()->user(), $record);
                        }),
                ])
                    ->label('Действия')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray')
                    ->button(),
            ]);
    }
}
