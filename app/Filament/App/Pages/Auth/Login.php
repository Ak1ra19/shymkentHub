<?php

namespace App\Filament\App\Pages\Auth;

use App\Models\User;
use App\Services\ActivityLogger;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $login = (string) $data['login'];

        $user = User::query()
            ->where('phone', $login)
            ->orWhere('iin_hash', hash('sha256', $login))
            ->first();

        if (! $user || ! Hash::check((string) $data['password'], $user->password)) {
            $this->throwLoginFailureValidationException();
        }

        if (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            $this->throwLoginFailureValidationException();
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));
        session()->regenerate();

        app(ActivityLogger::class)->log('auth.login', $user, $user);

        return app(LoginResponse::class);
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Телефон или ИИН')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Вход';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Вход в кабинет резидента';
    }

    protected function throwLoginFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => 'Неверные данные для входа или пользователь заблокирован.',
        ]);
    }
}
