<?php

namespace App\Filament\App\Pages\Auth;

use App\Enums\ConferenceRoomRequestStatus;
use App\Enums\WorkspaceBookingStatus;
use App\Models\User;
use App\Services\ActivityLogger;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Настройки профиля')
                    ->description('Данные, по которым администратор видит вас в заявках и бронированиях.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        $this->getNameFormComponent()
                            ->label('ФИО'),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->required()
                            ->maxLength(32)
                            ->unique(ignoreRecord: true),
                        $this->getEmailFormComponent()
                            ->label('Email')
                            ->required(false),
                        TextInput::make('position')
                            ->label('Должность')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('company')
                            ->label('Компания')
                            ->required()
                            ->maxLength(255),
                    ]),
                Section::make('Безопасность')
                    ->description('Пароль можно не заполнять, если менять его не нужно.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        $this->getPasswordFormComponent()
                            ->label('Новый пароль'),
                        $this->getPasswordConfirmationFormComponent()
                            ->label('Повторите пароль'),
                        $this->getCurrentPasswordFormComponent()
                            ->label('Текущий пароль'),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);

        if ($record instanceof User) {
            app(ActivityLogger::class)->log('profile.updated', $record, $record);
        }

        return $record;
    }

    public function content(Schema $schema): Schema
    {
        $components = [
            SchemaView::make('filament.app.pages.profile-overview')
                ->viewData(fn (): array => $this->profileOverviewData()),
            $this->getFormContentComponent(),
        ];

        if ($multiFactorAuthentication = $this->getMultiFactorAuthenticationContentComponent()) {
            $components[] = $multiFactorAuthentication;
        }

        return $schema->components($components);
    }

    /**
     * @return array<string, mixed>
     */
    private function profileOverviewData(): array
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            return [
                'user' => $user,
                'workspaceBookings' => collect(),
                'conferenceRequests' => collect(),
                'activeWorkspaceBookings' => 0,
                'pendingConferenceRequests' => 0,
            ];
        }

        return [
            'user' => $user,
            'workspaceBookings' => $user->workspaceBookings()
                ->with('workspace')
                ->latest('booking_date')
                ->latest('starts_at')
                ->limit(8)
                ->get(),
            'conferenceRequests' => $user->conferenceRoomRequests()
                ->latest('booking_date')
                ->latest('starts_at')
                ->limit(8)
                ->get(),
            'activeWorkspaceBookings' => $user->workspaceBookings()
                ->where('status', WorkspaceBookingStatus::Active)
                ->count(),
            'pendingConferenceRequests' => $user->conferenceRoomRequests()
                ->where('status', ConferenceRoomRequestStatus::Pending)
                ->count(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Профиль сохранен';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Мой профиль';
    }

    public static function getLabel(): string
    {
        return 'Мой профиль';
    }
}
