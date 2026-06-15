<?php

namespace App\Filament\App\Resources\WorkspaceBookings\Pages;

use App\Filament\App\Resources\WorkspaceBookings\Schemas\WorkspaceBookingForm;
use App\Filament\App\Resources\WorkspaceBookings\WorkspaceBookingResource;
use App\Models\WorkspaceBooking;
use App\Services\WorkspaceAvailability;
use App\Services\WorkspaceBookingService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ListWorkspaceBookings extends ListRecords
{
    protected static string $resource = WorkspaceBookingResource::class;

    protected static ?string $title = 'Общий зал';

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaView::make('filament.app.resources.workspace-bookings.pages.workspace-hall')
                    ->viewData(fn (): array => $this->workspaceHallData()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function createAction(): CreateAction
    {
        return CreateAction::make()
            ->label('Забронировать место')
            ->icon(Heroicon::Plus)
            ->modal()
            ->modalHeading('Бронирование рабочего места')
            ->modalSubmitActionLabel('Забронировать')
            ->modalWidth(Width::ThreeExtraLarge)
            ->schema(fn (Schema $schema): Schema => WorkspaceBookingForm::configure($schema, workspaceIsSelectable: false))
            ->fillForm(fn (array $arguments): array => $this->workspaceBookingDefaults($arguments))
            ->createAnother(false)
            ->successNotificationTitle('Рабочее место забронировано')
            ->using(fn (array $data): WorkspaceBooking => $this->createWorkspaceBooking($data));
    }

    /**
     * @return array{workspaces:mixed,dateLabel:string,scheduleLabel:string,availableCount:int,totalCount:int,bookedByUserCount:int}
     */
    private function workspaceHallData(): array
    {
        $availability = app(WorkspaceAvailability::class);
        $date = now()->toDateString();
        $workspaces = $availability->hallMapForUser(auth()->user(), $date);

        return [
            'workspaces' => $workspaces,
            'dateLabel' => now()->format('d.m.Y'),
            'scheduleLabel' => $availability->scheduleForDate($date)['label'],
            'availableCount' => $workspaces->where('can_book', true)->count(),
            'totalCount' => $workspaces->count(),
            'bookedByUserCount' => $workspaces->whereIn('status', ['mine', 'mine_full'])->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{workspace_id:int|null, booking_date:string, starts_at:string|null, ends_at:string|null, full_day:bool}
     */
    private function workspaceBookingDefaults(array $arguments): array
    {
        $workspaceId = filled($arguments['workspace'] ?? null)
            ? (int) $arguments['workspace']
            : null;

        $slot = $workspaceId
            ? app(WorkspaceAvailability::class)->firstAvailableSlotForWorkspace($workspaceId, now()->toDateString())
            : null;

        return [
            'workspace_id' => $workspaceId,
            'booking_date' => now()->toDateString(),
            'starts_at' => $slot['starts_at'] ?? null,
            'ends_at' => $slot['ends_at'] ?? null,
            'full_day' => false,
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
