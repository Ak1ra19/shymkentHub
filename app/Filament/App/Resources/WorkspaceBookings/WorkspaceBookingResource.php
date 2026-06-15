<?php

namespace App\Filament\App\Resources\WorkspaceBookings;

use App\Filament\App\Resources\WorkspaceBookings\Pages\ListWorkspaceBookings;
use App\Filament\App\Resources\WorkspaceBookings\Schemas\WorkspaceBookingForm;
use App\Filament\App\Resources\WorkspaceBookings\Tables\WorkspaceBookingsTable;
use App\Models\WorkspaceBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WorkspaceBookingResource extends Resource
{
    protected static ?string $model = WorkspaceBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Общий зал';

    protected static ?string $modelLabel = 'рабочее место';

    protected static ?string $pluralModelLabel = 'Общий зал';

    protected static string|UnitEnum|null $navigationGroup = 'Бронирования';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return WorkspaceBookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkspaceBookingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('workspace')
            ->whereBelongsTo(auth()->user())
            ->latest('booking_date')
            ->latest('starts_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkspaceBookings::route('/'),
        ];
    }
}
