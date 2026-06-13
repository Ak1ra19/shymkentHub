<?php

namespace App\Filament\Resources\ConferenceRoomRequests;

use App\Filament\Resources\ConferenceRoomRequests\Pages\ListConferenceRoomRequests;
use App\Filament\Resources\ConferenceRoomRequests\Tables\ConferenceRoomRequestsTable;
use App\Models\ConferenceRoomRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ConferenceRoomRequestResource extends Resource
{
    protected static ?string $model = ConferenceRoomRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Заявки на зал';

    protected static string|UnitEnum|null $navigationGroup = 'Бронирования';

    protected static ?string $modelLabel = 'заявка на конференц-зал';

    protected static ?string $pluralModelLabel = 'Заявки на конференц-зал';

    protected static ?int $navigationSort = 20;

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
        return ConferenceRoomRequestsTable::configure($table);
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
            'index' => ListConferenceRoomRequests::route('/'),
        ];
    }
}
