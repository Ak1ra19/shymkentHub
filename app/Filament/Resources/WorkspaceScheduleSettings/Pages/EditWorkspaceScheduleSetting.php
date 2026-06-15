<?php

namespace App\Filament\Resources\WorkspaceScheduleSettings\Pages;

use App\Filament\Resources\WorkspaceScheduleSettings\WorkspaceScheduleSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkspaceScheduleSetting extends EditRecord
{
    protected static string $resource = WorkspaceScheduleSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить'),
        ];
    }
}
