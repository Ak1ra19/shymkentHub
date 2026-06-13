<?php

namespace App\Filament\App\Resources\ConferenceRoomRequests\Pages;

use App\Filament\App\Resources\ConferenceRoomRequests\ConferenceRoomRequestResource;
use App\Models\ConferenceRoomRequest;
use App\Services\ConferenceRoomRequestService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ListConferenceRoomRequests extends ListRecords
{
    protected static string $resource = ConferenceRoomRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Забронировать зал')
                ->icon(Heroicon::Plus)
                ->modal()
                ->modalHeading('Заявка на конференц-зал')
                ->modalSubmitActionLabel('Отправить заявку')
                ->modalWidth(Width::ThreeExtraLarge)
                ->createAnother(false)
                ->successNotificationTitle('Заявка отправлена')
                ->using(fn (array $data): ConferenceRoomRequest => $this->createConferenceRoomRequest($data)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createConferenceRoomRequest(array $data): ConferenceRoomRequest
    {
        try {
            return app(ConferenceRoomRequestService::class)->create(auth()->user(), [
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
