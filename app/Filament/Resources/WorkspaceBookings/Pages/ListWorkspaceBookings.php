<?php

namespace App\Filament\Resources\WorkspaceBookings\Pages;

use App\Filament\Resources\WorkspaceBookings\WorkspaceBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkspaceBookings extends ListRecords
{
    protected static string $resource = WorkspaceBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
