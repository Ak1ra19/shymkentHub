<?php

namespace App\Filament\App\Resources\Events\Pages;

use App\Filament\App\Resources\Events\EventResource;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;
}
