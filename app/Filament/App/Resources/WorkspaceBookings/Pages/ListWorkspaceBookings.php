<?php

namespace App\Filament\App\Resources\WorkspaceBookings\Pages;

use App\Filament\App\Resources\WorkspaceBookings\WorkspaceBookingResource;
use App\Models\WorkspaceBooking;
use App\Services\WorkspaceBookingService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ListWorkspaceBookings extends ListRecords
{
    protected static string $resource = WorkspaceBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Забронировать место')
                ->icon(Heroicon::Plus)
                ->modal()
                ->modalHeading('Бронирование рабочего места')
                ->modalSubmitActionLabel('Забронировать')
                ->modalWidth(Width::ThreeExtraLarge)
                ->createAnother(false)
                ->successNotificationTitle('Рабочее место забронировано')
                ->using(fn (array $data): WorkspaceBooking => $this->createWorkspaceBooking($data)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createWorkspaceBooking(array $data): WorkspaceBooking
    {
        try {
            return app(WorkspaceBookingService::class)->create(auth()->user(), [
                ...$data,
                'booking_date' => $this->dateValue($data['booking_date']),
                'starts_at' => $this->timeValue($data['starts_at']),
                'ends_at' => $this->timeValue($data['ends_at']),
            ]);
        } catch (ValidationException $exception) {
            throw $this->modalValidationException($exception);
        }
    }

    private function dateValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->toDateString();
        }

        return CarbonImmutable::parse((string) $value)->toDateString();
    }

    private function timeValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i');
        }

        return mb_substr((string) $value, 0, 5);
    }

    private function modalValidationException(ValidationException $exception): ValidationException
    {
        $actionIndex = array_key_last($this->mountedActions ?? []) ?? 0;
        $messages = [];

        foreach ($exception->errors() as $field => $fieldMessages) {
            $messages["mountedActions.{$actionIndex}.data.{$field}"] = $fieldMessages;
        }

        return ValidationException::withMessages($messages);
    }
}
