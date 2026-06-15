<?php

namespace App\Filament\Resources\WorkspaceScheduleSettings\Pages;

use App\Filament\Resources\WorkspaceScheduleSettings\WorkspaceScheduleSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkspaceScheduleSettings extends ListRecords
{
    protected static string $resource = WorkspaceScheduleSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить режим'),
        ];
    }
}
