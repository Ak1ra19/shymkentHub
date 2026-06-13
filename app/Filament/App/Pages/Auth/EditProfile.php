<?php

namespace App\Filament\App\Pages\Auth;

use App\Models\User;
use App\Services\ActivityLogger;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
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
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
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

    public function getTitle(): string|Htmlable
    {
        return 'Профиль';
    }

    public static function getLabel(): string
    {
        return 'Профиль';
    }
}
