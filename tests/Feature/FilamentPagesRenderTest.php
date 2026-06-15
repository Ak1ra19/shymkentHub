<?php

namespace Tests\Feature;

use App\Filament\Resources\Workspaces\Pages\ListWorkspaces;
use App\Models\Event;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);

        parent::tearDown();
    }

    public function test_resident_filament_pages_render(): void
    {
        $user = User::factory()->create();
        Workspace::factory()->create(['number' => 1]);

        Event::factory()->create([
            'title' => 'День открытых проектов',
            'event_date' => now()->addDay()->toDateString(),
            'event_time' => '18:00',
        ]);

        $this->actingAs($user)
            ->get('/app')
            ->assertOk()
            ->assertSee('Главная')
            ->assertSee('Сводка на сегодня')
            ->assertSee('Сегодня в ShymkentHub')
            ->assertSee('День открытых проектов');

        $this->actingAs($user)
            ->get('/app/workspace-bookings')
            ->assertOk()
            ->assertSee('Общий зал')
            ->assertSee('Бронирование рабочих мест')
            ->assertSee('Доступно')
            ->assertDontSee('Мои бронирования');

        $this->actingAs($user)
            ->get('/app/profile')
            ->assertOk()
            ->assertSee('Мой профиль')
            ->assertSee('Мои бронирования')
            ->assertSee('Данные резидента')
            ->assertSee('Настройки профиля')
            ->assertSee('Забронировать место');

        $this->actingAs($user)
            ->get('/app/workspace-bookings/create')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/app/conference-room-requests')
            ->assertOk()
            ->assertSee('Конференц-зал')
            ->assertSee('Забронировать зал');

        $this->actingAs($user)
            ->get('/app/events')
            ->assertNotFound();
    }

    public function test_admin_filament_pages_render(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Главная')
            ->assertSee('Заявки на согласование')
            ->assertDontSee('Журнал действий');

        $this->actingAs($admin)
            ->get('/admin/workspaces')
            ->assertOk()
            ->assertSee('Справочник мест')
            ->assertSee('Закреплено за')
            ->assertSee('Добавить место');

        $this->actingAs($admin)
            ->get('/admin/workspace-schedule-settings')
            ->assertOk()
            ->assertSee('Режим работы зала')
            ->assertSee('Добавить режим');

        $this->actingAs($admin)
            ->get('/admin/booking-calendar')
            ->assertOk()
            ->assertSee('Календарь броней')
            ->assertSee('Тип бронирования');

        $this->actingAs($admin)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Отчет по бронированиям')
            ->assertSee('Скачать .xlsx');
    }

    public function test_admin_can_create_workspace_range_through_filament(): void
    {
        Filament::setCurrentPanel('admin');

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ListWorkspaces::class)
            ->callAction('createRange', data: [
                'start_number' => 30,
                'count' => 3,
                'zone' => 'Тихая зона',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('workspaces', [
            'number' => 30,
            'zone' => 'Тихая зона',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('workspaces', [
            'number' => 32,
            'zone' => 'Тихая зона',
            'is_active' => true,
        ]);
    }
}
