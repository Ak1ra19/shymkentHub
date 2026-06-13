<?php

namespace App\Filament\App\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\ActivityLogger;
use Closure;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use SensitiveParameter;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent()
                    ->label('ФИО'),
                TextInput::make('iin')
                    ->label('ИИН')
                    ->required()
                    ->length(12)
                    ->numeric()
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (User::query()->where('iin_hash', hash('sha256', (string) $value))->exists()) {
                                $fail('Пользователь с таким ИИН уже зарегистрирован.');
                            }
                        },
                    ]),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->required()
                    ->maxLength(32)
                    ->unique(User::class, 'phone'),
                $this->getEmailFormComponent()
                    ->label('Email')
                    ->required(false)
                    ->unique(User::class, 'email', ignoreRecord: true),
                TextInput::make('position')
                    ->label('Должность')
                    ->required()
                    ->maxLength(255),
                TextInput::make('company')
                    ->label('Компания')
                    ->required()
                    ->maxLength(255),
                $this->getPasswordFormComponent()
                    ->rule(Password::defaults()),
                $this->getPasswordConfirmationFormComponent(),
                Checkbox::make('rules_accepted')
                    ->label('Я ознакомлен с правилами')
                    ->accepted()
                    ->validationMessages([
                        'accepted' => 'Подтвердите ознакомление с правилами.',
                    ]),
                Html::make(new HtmlString(
                    '<a class="text-primary-600 underline dark:text-primary-400" href="'.route('resident-instructions').'" target="_blank" rel="noopener">Открыть PDF-инструкцию для резидентов</a>'
                )),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Регистрация';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Регистрация резидента';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        $user = User::create([
            'name' => $data['name'],
            'iin' => $data['iin'],
            'iin_hash' => hash('sha256', (string) $data['iin']),
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'position' => $data['position'],
            'company' => $data['company'],
            'role' => UserRole::User,
            'rules_accepted_at' => now(),
            'password' => $data['password'],
        ]);

        $user->notify(new SystemNotification(
            title: 'Добро пожаловать',
            body: 'Профиль создан. Теперь можно бронировать рабочие места и отправлять заявки на конференц-зал.',
            url: Filament::getUrl(),
        ));

        app(ActivityLogger::class)->log('auth.registered', $user, $user);

        return $user;
    }
}
