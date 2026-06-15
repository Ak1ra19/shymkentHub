<?php

namespace Tests\Feature;

use App\Enums\ConferenceRoomRequestStatus;
use App\Enums\WorkspaceBookingStatus;
use App\Filament\App\Resources\ConferenceRoomRequests\Pages\ListConferenceRoomRequests as AppListConferenceRoomRequests;
use App\Filament\App\Resources\WorkspaceBookings\Pages\ListWorkspaceBookings as AppListWorkspaceBookings;
use App\Models\ConferenceRoomRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBooking;
use App\Models\WorkspaceScheduleSetting;
use App\Services\ConferenceRoomAvailability;
use App\Services\WorkspaceAvailability;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_booking_is_allowed_only_for_current_day(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['number' => 1]);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->callAction('create', data: [
                'booking_date' => now()->addDay()->toDateString(),
                'starts_at' => '09:00',
                'ends_at' => '10:00',
                'workspace_id' => $workspace->id,
            ])
            ->assertHasActionErrors(['booking_date']);

        $this->assertDatabaseMissing('workspace_bookings', [
            'workspace_id' => $workspace->id,
        ]);
    }

    public function test_workspace_booking_rejects_overlapping_active_booking(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['number' => 7]);

        WorkspaceBooking::factory()->create([
            'workspace_id' => $workspace->id,
            'workspace_number' => $workspace->number,
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'status' => WorkspaceBookingStatus::Active,
        ]);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'workspace_id' => $workspace->id,
                'starts_at' => '10:00',
                'ends_at' => '12:00',
            ])
            ->assertHasActionErrors(['starts_at']);
    }

    public function test_workspace_availability_suggests_first_free_hour_after_busy_interval(): void
    {
        $workspace = Workspace::factory()->create(['number' => 22]);

        WorkspaceBooking::factory()->create([
            'workspace_id' => $workspace->id,
            'workspace_number' => $workspace->number,
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '13:00',
            'status' => WorkspaceBookingStatus::Active,
        ]);

        $slot = app(WorkspaceAvailability::class)->firstAvailableSlotForWorkspace($workspace, now()->toDateString());

        $this->assertSame([
            'starts_at' => '13:00',
            'ends_at' => '14:00',
        ], $slot);
    }

    public function test_workspace_booking_modal_sets_nearest_free_slot_after_workspace_selection(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['number' => 23]);

        WorkspaceBooking::factory()->create([
            'workspace_id' => $workspace->id,
            'workspace_number' => $workspace->number,
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '13:00',
            'status' => WorkspaceBookingStatus::Active,
        ]);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->mountAction('create')
            ->set('mountedActions.0.data.workspace_id', $workspace->id)
            ->assertSet('mountedActions.0.data.starts_at', fn (string $state): bool => str_contains($state, '13:00'))
            ->assertSet('mountedActions.0.data.ends_at', fn (string $state): bool => str_contains($state, '14:00'))
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('workspace_bookings', [
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'starts_at' => '13:00',
            'ends_at' => '14:00',
        ]);
    }

    public function test_workspace_booking_modal_keeps_end_time_one_hour_after_changed_start_time(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['number' => 26]);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->mountAction('create')
            ->set('mountedActions.0.data.starts_at', '12:00')
            ->assertSet('mountedActions.0.data.ends_at', '13:00')
            ->set('mountedActions.0.data.workspace_id', $workspace->id)
            ->assertSet('mountedActions.0.data.starts_at', '12:00')
            ->assertSet('mountedActions.0.data.ends_at', '13:00')
            ->set('mountedActions.0.data.ends_at', null)
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('workspace_bookings', [
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'workspace_number' => 26,
            'starts_at' => '12:00',
            'ends_at' => '13:00',
        ]);
    }

    public function test_workspace_schedule_setting_changes_first_available_slot(): void
    {
        $workspace = Workspace::factory()->create(['number' => 28]);

        WorkspaceScheduleSetting::factory()->create([
            'starts_on' => now()->toDateString(),
            'starts_at' => '10:00',
            'ends_at' => '19:00',
        ]);

        $slot = app(WorkspaceAvailability::class)->firstAvailableSlotForWorkspace($workspace, now()->toDateString());

        $this->assertSame([
            'starts_at' => '10:00',
            'ends_at' => '11:00',
        ], $slot);
    }

    public function test_workspace_booking_modal_can_book_full_workday(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['number' => 29]);

        WorkspaceScheduleSetting::factory()->create([
            'starts_on' => now()->toDateString(),
            'starts_at' => '10:00',
            'ends_at' => '18:00',
        ]);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->mountAction('create')
            ->set('mountedActions.0.data.workspace_id', $workspace->id)
            ->set('mountedActions.0.data.full_day', true)
            ->assertSet('mountedActions.0.data.starts_at', '10:00')
            ->assertSet('mountedActions.0.data.ends_at', '18:00')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('workspace_bookings', [
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'workspace_number' => 29,
            'starts_at' => '10:00',
            'ends_at' => '18:00',
        ]);

        $workspaceState = app(WorkspaceAvailability::class)
            ->hallMapForUser($user, now()->toDateString())
            ->firstWhere('id', $workspace->id);

        $this->assertSame('mine_full', $workspaceState['status']);
    }

    public function test_workspace_start_time_options_skip_busy_intervals(): void
    {
        $workspace = Workspace::factory()->create(['number' => 27]);

        WorkspaceBooking::factory()->create([
            'workspace_id' => $workspace->id,
            'workspace_number' => $workspace->number,
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '13:00',
            'status' => WorkspaceBookingStatus::Active,
        ]);

        $options = app(WorkspaceAvailability::class)->startTimeOptionsForWorkspace($workspace, now()->toDateString());

        $this->assertArrayNotHasKey('09:00', $options);
        $this->assertArrayNotHasKey('12:30', $options);
        $this->assertArrayHasKey('13:00', $options);
    }

    public function test_workspace_assigned_to_user_is_hidden_from_other_users_and_bookable_by_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $assignedWorkspace = Workspace::factory()->create([
            'number' => 24,
            'assigned_user_id' => $owner->id,
        ]);
        $sharedWorkspace = Workspace::factory()->create(['number' => 25]);

        $otherOptions = app(WorkspaceAvailability::class)->optionsForSelection(
            now()->toDateString(),
            null,
            null,
            $otherUser,
        );

        $this->assertArrayNotHasKey($assignedWorkspace->id, $otherOptions);
        $this->assertArrayHasKey($sharedWorkspace->id, $otherOptions);

        Livewire::actingAs($otherUser)
            ->test(AppListWorkspaceBookings::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '13:00',
                'ends_at' => '14:00',
                'workspace_id' => $assignedWorkspace->id,
            ])
            ->assertHasActionErrors(['workspace_id']);

        Livewire::actingAs($owner)
            ->test(AppListWorkspaceBookings::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '13:00',
                'ends_at' => '14:00',
                'workspace_id' => $assignedWorkspace->id,
            ])
            ->assertHasNoActionErrors()
            ->assertActionNotMounted();
    }

    public function test_conference_room_rejects_time_conflicts(): void
    {
        $user = User::factory()->create();

        ConferenceRoomRequest::factory()->create([
            'booking_date' => now()->toDateString(),
            'starts_at' => '13:00',
            'ends_at' => '14:00',
            'status' => ConferenceRoomRequestStatus::Approved,
        ]);

        Livewire::actingAs($user)
            ->test(AppListConferenceRoomRequests::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '13:30',
                'ends_at' => '14:30',
                'purpose' => 'Frontend meetup',
            ])
            ->assertHasActionErrors(['starts_at']);
    }

    public function test_conference_room_availability_suggests_first_free_hour_after_busy_interval(): void
    {
        ConferenceRoomRequest::factory()->create([
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '13:00',
            'status' => ConferenceRoomRequestStatus::Approved,
        ]);

        $slot = app(ConferenceRoomAvailability::class)->firstAvailableSlot(now()->toDateString());

        $this->assertSame([
            'starts_at' => '13:00',
            'ends_at' => '14:00',
        ], $slot);
    }

    public function test_conference_room_modal_sets_nearest_free_slot_on_open(): void
    {
        $user = User::factory()->create();

        ConferenceRoomRequest::factory()->create([
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '13:00',
            'status' => ConferenceRoomRequestStatus::Approved,
        ]);

        Livewire::actingAs($user)
            ->test(AppListConferenceRoomRequests::class)
            ->mountAction('create')
            ->assertSet('mountedActions.0.data.starts_at', fn (string $state): bool => str_contains($state, '13:00'))
            ->assertSet('mountedActions.0.data.ends_at', fn (string $state): bool => str_contains($state, '14:00'))
            ->set('mountedActions.0.data.purpose', 'Созвон с партнерами')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('conference_room_requests', [
            'user_id' => $user->id,
            'purpose' => 'Созвон с партнерами',
            'starts_at' => '13:00',
            'ends_at' => '14:00',
        ]);
    }

    public function test_conference_room_modal_keeps_end_time_one_hour_after_changed_start_time(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AppListConferenceRoomRequests::class)
            ->mountAction('create')
            ->set('mountedActions.0.data.starts_at', '12:00')
            ->assertSet('mountedActions.0.data.ends_at', '13:00')
            ->set('mountedActions.0.data.ends_at', null)
            ->set('mountedActions.0.data.purpose', 'Встреча с резидентами')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('conference_room_requests', [
            'user_id' => $user->id,
            'purpose' => 'Встреча с резидентами',
            'starts_at' => '12:00',
            'ends_at' => '13:00',
            'status' => ConferenceRoomRequestStatus::Pending->value,
        ]);
    }

    public function test_conference_room_end_time_options_stop_before_next_busy_interval(): void
    {
        ConferenceRoomRequest::factory()->create([
            'booking_date' => now()->toDateString(),
            'starts_at' => '14:00',
            'ends_at' => '15:00',
            'status' => ConferenceRoomRequestStatus::Approved,
        ]);

        $options = app(ConferenceRoomAvailability::class)->endTimeOptions(now()->toDateString(), '13:00');

        $this->assertArrayHasKey('13:30', $options);
        $this->assertArrayHasKey('14:00', $options);
        $this->assertArrayNotHasKey('14:30', $options);
    }

    public function test_conference_room_enforces_two_hour_daily_limit(): void
    {
        $user = User::factory()->create();

        ConferenceRoomRequest::factory()->for($user)->create([
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '10:30',
            'status' => ConferenceRoomRequestStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(AppListConferenceRoomRequests::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '15:00',
                'ends_at' => '16:00',
                'purpose' => 'Planning session',
            ])
            ->assertHasActionErrors(['ends_at']);
    }

    public function test_cancelled_conference_room_request_does_not_count_toward_daily_limit(): void
    {
        $user = User::factory()->create();

        ConferenceRoomRequest::factory()->for($user)->create([
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '10:30',
            'status' => ConferenceRoomRequestStatus::Cancelled,
        ]);

        Livewire::actingAs($user)
            ->test(AppListConferenceRoomRequests::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '15:00',
                'ends_at' => '16:00',
                'purpose' => 'Planning session',
            ])
            ->assertHasNoActionErrors()
            ->assertActionNotMounted();

        $this->assertDatabaseHas('conference_room_requests', [
            'user_id' => $user->id,
            'purpose' => 'Planning session',
            'status' => ConferenceRoomRequestStatus::Pending->value,
        ]);
    }

    public function test_booking_creates_notification_and_activity_log(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['number' => 12]);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '09:00',
                'ends_at' => '10:00',
                'workspace_id' => $workspace->id,
            ])
            ->assertHasNoActionErrors()
            ->assertActionNotMounted();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $user->id,
            'event' => 'workspace_booking.created',
        ]);
    }

    public function test_filament_workspace_booking_modal_only_blocks_overlapping_time(): void
    {
        $workspace = Workspace::factory()->create(['number' => 19]);

        WorkspaceBooking::factory()->create([
            'workspace_id' => $workspace->id,
            'workspace_number' => $workspace->number,
            'booking_date' => now()->toDateString(),
            'starts_at' => '15:00',
            'ends_at' => '16:00',
            'status' => WorkspaceBookingStatus::Active,
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'workspace_id' => $workspace->id,
                'starts_at' => '15:30',
                'ends_at' => '15:45',
            ])
            ->assertHasActionErrors(['starts_at']);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'workspace_id' => $workspace->id,
                'starts_at' => '16:00',
                'ends_at' => '17:00',
            ])
            ->assertHasNoActionErrors()
            ->assertActionNotMounted();

        $this->assertDatabaseHas('workspace_bookings', [
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'workspace_number' => 19,
            'starts_at' => '16:00',
            'ends_at' => '17:00',
        ]);
    }

    public function test_workspace_booking_header_action_opens_modal_and_creates_booking(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['number' => 5]);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->assertActionExists('create', fn (Action $action): bool => $action->getUrl() === null)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '11:00',
                'ends_at' => '12:00',
                'workspace_id' => $workspace->id,
            ])
            ->assertHasNoActionErrors()
            ->assertActionNotMounted();

        $this->assertDatabaseHas('workspace_bookings', [
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
            'workspace_number' => 5,
            'status' => WorkspaceBookingStatus::Active->value,
        ]);
    }

    public function test_conference_room_header_action_opens_modal_and_creates_request(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AppListConferenceRoomRequests::class)
            ->assertActionExists('create', fn (Action $action): bool => $action->getUrl() === null)
            ->assertActionHasLabel('create', 'Забронировать зал')
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '14:00',
                'ends_at' => '15:00',
                'purpose' => 'Мероприятие по Frontend разработке',
            ])
            ->assertHasNoActionErrors()
            ->assertActionNotMounted();

        $this->assertDatabaseHas('conference_room_requests', [
            'user_id' => $user->id,
            'purpose' => 'Мероприятие по Frontend разработке',
            'status' => ConferenceRoomRequestStatus::Pending->value,
        ]);
    }

    public function test_modal_workspace_booking_action_shows_conflict_errors(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['number' => 8]);

        WorkspaceBooking::factory()->create([
            'workspace_id' => $workspace->id,
            'workspace_number' => $workspace->number,
            'booking_date' => now()->toDateString(),
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'status' => WorkspaceBookingStatus::Active,
        ]);

        Livewire::actingAs($user)
            ->test(AppListWorkspaceBookings::class)
            ->mountAction('create')
            ->set('mountedActions.0.data.booking_date', now()->toDateString())
            ->set('mountedActions.0.data.workspace_id', $workspace->id)
            ->set('mountedActions.0.data.starts_at', '10:00')
            ->set('mountedActions.0.data.ends_at', '12:00')
            ->callMountedAction()
            ->assertHasActionErrors(['starts_at']);
    }

    public function test_modal_conference_room_action_shows_conflict_errors(): void
    {
        $user = User::factory()->create();

        ConferenceRoomRequest::factory()->create([
            'booking_date' => now()->toDateString(),
            'starts_at' => '13:00',
            'ends_at' => '14:00',
            'status' => ConferenceRoomRequestStatus::Approved,
        ]);

        Livewire::actingAs($user)
            ->test(AppListConferenceRoomRequests::class)
            ->callAction('create', data: [
                'booking_date' => now()->toDateString(),
                'starts_at' => '13:30',
                'ends_at' => '14:30',
                'purpose' => 'Frontend meetup',
            ])
            ->assertHasActionErrors(['starts_at']);
    }
}
