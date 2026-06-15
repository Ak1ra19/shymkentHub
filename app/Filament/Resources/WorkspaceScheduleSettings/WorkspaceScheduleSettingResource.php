<?php

namespace App\Filament\Resources\WorkspaceScheduleSettings;

use App\Filament\Resources\WorkspaceScheduleSettings\Pages\CreateWorkspaceScheduleSetting;
use App\Filament\Resources\WorkspaceScheduleSettings\Pages\EditWorkspaceScheduleSetting;
use App\Filament\Resources\WorkspaceScheduleSettings\Pages\ListWorkspaceScheduleSettings;
use App\Filament\Resources\WorkspaceScheduleSettings\Schemas\WorkspaceScheduleSettingForm;
use App\Filament\Resources\WorkspaceScheduleSettings\Tables\WorkspaceScheduleSettingsTable;
use App\Models\WorkspaceScheduleSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WorkspaceScheduleSettingResource extends Resource
{
    protected static ?string $model = WorkspaceScheduleSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Режим работы зала';

    protected static string|UnitEnum|null $navigationGroup = 'Общий зал';

    protected static ?string $modelLabel = 'режим работы';

    protected static ?string $pluralModelLabel = 'Режим работы зала';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return WorkspaceScheduleSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkspaceScheduleSettingsTable::configure($table);
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
            'index' => ListWorkspaceScheduleSettings::route('/'),
            'create' => CreateWorkspaceScheduleSetting::route('/create'),
            'edit' => EditWorkspaceScheduleSetting::route('/{record}/edit'),
        ];
    }
}
