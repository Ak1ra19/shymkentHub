<?php

namespace App\Filament\Resources\WorkspaceScheduleSettings\Schemas;

use App\Services\WorkspaceScheduleService;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class WorkspaceScheduleSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Рабочий день общего зала')
                    ->description('Новая запись начинает действовать с указанной даты. Для будущего месяца добавьте отдельный режим с первым днем месяца.')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                DatePicker::make('starts_on')
                                    ->label('Действует с')
                                    ->native(false)
                                    ->displayFormat('d.m.Y')
                                    ->default(now()->toDateString())
                                    ->minDate(now()->startOfDay())
                                    ->unique(ignoreRecord: true)
                                    ->required(),
                                TimePicker::make('starts_at')
                                    ->label('Начало дня')
                                    ->seconds(false)
                                    ->minutesStep(30)
                                    ->native(false)
                                    ->default(WorkspaceScheduleService::DEFAULT_START)
                                    ->required(),
                                TimePicker::make('ends_at')
                                    ->label('Конец дня')
                                    ->seconds(false)
                                    ->minutesStep(30)
                                    ->native(false)
                                    ->default(WorkspaceScheduleService::DEFAULT_END)
                                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                        $startsAt = self::timeValue($get('starts_at'));
                                        $endsAt = self::timeValue($value);

                                        if ($startsAt !== null && $endsAt !== null && $endsAt <= $startsAt) {
                                            $fail('Конец дня должен быть позже начала.');
                                        }
                                    })
                                    ->required(),
                            ]),
                        TextInput::make('note')
                            ->label('Комментарий')
                            ->placeholder('Например: летний график или график с 1 июля')
                            ->maxLength(255),
                    ]),
            ]);
    }

    private static function timeValue(mixed $value): ?string
    {
        preg_match('/\d{2}:\d{2}/', (string) $value, $matches);

        return $matches[0] ?? null;
    }
}
