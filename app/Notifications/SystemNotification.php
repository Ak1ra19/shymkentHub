<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly array $data = [],
        private readonly ?string $url = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->databaseMessage();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->databaseMessage();
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseMessage(): array
    {
        $notification = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon(Heroicon::OutlinedBell)
            ->iconColor('primary')
            ->color('primary');

        if ($this->url) {
            $notification->actions([
                Action::make('open')
                    ->label('Открыть')
                    ->button()
                    ->url($this->url)
                    ->markAsRead(),
            ]);
        }

        return [
            ...$notification->getDatabaseMessage(),
            'meta' => $this->data,
        ];
    }
}
