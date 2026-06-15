<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Auth\EditProfile as AppEditProfile;
use App\Filament\App\Pages\Auth\Login as AppLogin;
use App\Filament\App\Pages\Auth\Register as AppRegister;
use App\Models\User;
use App\Notifications\SystemNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Filament::setCurrentPanel(null);
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);

        parent::tearDown();
    }

    public function test_registration_requires_rules_acceptance(): void
    {
        Filament::setCurrentPanel('app');

        Livewire::test(AppRegister::class)
            ->fillForm([
                'name' => 'Resident',
                'iin' => '123456789012',
                'phone' => '+77071234567',
                'position' => 'Developer',
                'company' => 'Acme',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasErrors(['data.rules_accepted']);

        $this->assertGuest();
    }

    public function test_user_can_register_and_login_by_phone_or_iin(): void
    {
        Filament::setCurrentPanel('app');

        Livewire::test(AppRegister::class)
            ->fillForm([
                'name' => 'Resident',
                'iin' => '123456789012',
                'phone' => '+77071234567',
                'position' => 'Developer',
                'company' => 'Acme',
                'password' => 'password',
                'passwordConfirmation' => 'password',
                'rules_accepted' => true,
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $this->assertAuthenticated();
        Auth::logout();
        $this->assertGuest();

        Livewire::test(AppLogin::class)
            ->fillForm([
                'login' => '+77071234567',
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        Auth::logout();

        Livewire::test(AppLogin::class)
            ->fillForm([
                'login' => '123456789012',
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();
    }

    public function test_user_can_register_through_filament_app_panel(): void
    {
        Filament::setCurrentPanel('app');

        Livewire::test(AppRegister::class)
            ->fillForm([
                'name' => 'Filament Resident',
                'iin' => '123456789013',
                'phone' => '+77071234568',
                'email' => 'resident@example.com',
                'position' => 'Designer',
                'company' => 'Acme',
                'password' => 'password',
                'passwordConfirmation' => 'password',
                'rules_accepted' => true,
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Filament Resident',
            'phone' => '+77071234568',
            'role' => 'user',
        ]);
    }

    public function test_user_can_login_through_filament_app_panel_by_iin(): void
    {
        Filament::setCurrentPanel('app');

        $user = User::factory()->create([
            'iin' => '123456789014',
            'iin_hash' => hash('sha256', '123456789014'),
            'password' => 'password',
        ]);

        Livewire::test(AppLogin::class)
            ->fillForm([
                'login' => '123456789014',
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_blocked_user_cannot_login(): void
    {
        Filament::setCurrentPanel('app');

        $user = User::factory()->blocked()->create([
            'phone' => '+77070000003',
            'password' => 'password',
        ]);

        Livewire::test(AppLogin::class)
            ->fillForm([
                'login' => $user->phone,
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.login']);

        $this->assertGuest();
    }

    public function test_filament_panels_are_split_by_role(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->get('/app/register')->assertOk();

        $this->actingAs($user)
            ->get('/app')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_admin_reports_are_served_inside_filament_admin_panel(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get('/admin/reports')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk();
    }

    public function test_user_can_update_profile(): void
    {
        Filament::setCurrentPanel('app');

        $user = User::factory()->create([
            'phone' => '+77070000004',
        ]);

        Livewire::actingAs($user)
            ->test(AppEditProfile::class)
            ->fillForm([
                'name' => 'Updated Resident',
                'phone' => '+77070000005',
                'email' => 'updated@example.com',
                'position' => 'Product Manager',
                'company' => 'Updated Company',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Resident',
            'phone' => '+77070000005',
            'company' => 'Updated Company',
        ]);
    }

    public function test_user_receives_database_notifications_in_filament_panel(): void
    {
        $user = User::factory()->create();

        $user->notify(new SystemNotification(
            title: 'Тестовое уведомление',
            body: 'Текст уведомления',
        ));

        $this->actingAs($user)
            ->get('/app')
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertSame('filament', $user->notifications()->first()->data['format']);
    }
}
