<?php

namespace App\Filament\Resources\WorkspaceBookings;

use App\Filament\Resources\WorkspaceBookings\Pages\ListWorkspaceBookings;
use App\Filament\Resources\WorkspaceBookings\Tables\WorkspaceBookingsTable;
use App\Models\WorkspaceBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class WorkspaceBookingResource extends Resource
{
    protected static ?string $model = WorkspaceBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Брони мест';

    protected static string|UnitEnum|null $navigationGroup = 'Бронирования';

    protected static ?string $modelLabel = 'бронирование рабочего места';

    protected static ?string $pluralModelLabel = 'Брони рабочих мест';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return WorkspaceBookingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'workspace']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkspaceBookings::route('/'),
        ];
    }
}
