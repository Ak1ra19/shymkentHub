<?php

namespace App\Filament\Resources\ConferenceRoomRequests\Pages;

use App\Filament\Resources\ConferenceRoomRequests\ConferenceRoomRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListConferenceRoomRequests extends ListRecords
{
    protected static string $resource = ConferenceRoomRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
