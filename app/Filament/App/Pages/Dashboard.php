<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Главная';

    protected static ?string $title = 'Главная';

    protected static ?int $navigationSort = 0;

    protected ?string $subheading = 'Ближайшие мероприятия, быстрые действия и личная сводка на сегодня.';

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 12,
        ];
    }
}
