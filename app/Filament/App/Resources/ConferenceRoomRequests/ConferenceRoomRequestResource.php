<?php

namespace App\Filament\App\Resources\ConferenceRoomRequests;

use App\Filament\App\Resources\ConferenceRoomRequests\Pages\ListConferenceRoomRequests;
use App\Filament\App\Resources\ConferenceRoomRequests\Schemas\ConferenceRoomRequestForm;
use App\Filament\App\Resources\ConferenceRoomRequests\Tables\ConferenceRoomRequestsTable;
use App\Models\ConferenceRoomRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ConferenceRoomRequestResource extends Resource
{
    protected static ?string $model = ConferenceRoomRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Конференц-зал';

    protected static ?string $modelLabel = 'заявка на конференц-зал';

    protected static ?string $pluralModelLabel = 'Конференц-зал';

    protected static string|UnitEnum|null $navigationGroup = 'Бронирования';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ConferenceRoomRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConferenceRoomRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereBelongsTo(auth()->user())
            ->latest('booking_date')
            ->latest('starts_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConferenceRoomRequests::route('/'),
        ];
    }
}
