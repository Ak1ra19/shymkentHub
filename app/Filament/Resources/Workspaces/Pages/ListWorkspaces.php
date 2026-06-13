<?php

namespace App\Filament\Resources\Workspaces\Pages;

use App\Filament\Resources\Workspaces\WorkspaceResource;
use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ListWorkspaces extends ListRecords
{
    protected static string $resource = WorkspaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить место'),
            Action::make('createRange')
                ->label('Добавить несколько')
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('start_number')
                        ->label('Первый номер')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(255)
                        ->default(fn (): int => ((int) Workspace::query()->max('number')) + 1)
                        ->required(),
                    TextInput::make('count')
                        ->label('Количество мест')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(255)
                        ->default(30)
                        ->required(),
                    TextInput::make('zone')
                        ->label('Зона')
                        ->default('Общий зал')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $startNumber = (int) $data['start_number'];
                    $count = (int) $data['count'];
                    $numbers = range($startNumber, $startNumber + $count - 1);

                    if (max($numbers) > 255) {
                        Notification::make()
                            ->title('Слишком большой диапазон')
                            ->body('Номер рабочего места не может быть больше 255.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $existingNumbers = Workspace::query()
                        ->whereIn('number', $numbers)
                        ->pluck('number')
                        ->all();

                    if ($existingNumbers !== []) {
                        Notification::make()
                            ->title('Номера уже заняты')
                            ->body('Проверьте номера: '.collect($existingNumbers)->sort()->implode(', '))
                            ->danger()
                            ->send();

                        return;
                    }

                    DB::transaction(function () use ($numbers, $data): void {
                        foreach ($numbers as $number) {
                            Workspace::create([
                                'number' => $number,
                                'label' => null,
                                'zone' => $data['zone'] ?: null,
                                'sort_order' => $number,
                                'is_active' => true,
                            ]);
                        }
                    });

                    Notification::make()
                        ->title('Рабочие места добавлены')
                        ->body('Создано мест: '.$count)
                        ->success()
                        ->send();
                }),
        ];
    }
}
