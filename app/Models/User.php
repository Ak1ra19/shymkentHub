<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'iin',
    'iin_hash',
    'phone',
    'position',
    'company',
    'role',
    'is_blocked',
    'rules_accepted_at',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_blocked) {
            return false;
        }

        if ($panel->getId() === 'admin') {
            return $this->isAdmin();
        }

        return $panel->getId() === 'app';
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * @return HasMany<WorkspaceBooking, $this>
     */
    public function workspaceBookings(): HasMany
    {
        return $this->hasMany(WorkspaceBooking::class);
    }

    /**
     * @return HasMany<Workspace, $this>
     */
    public function assignedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'assigned_user_id');
    }

    /**
     * @return HasMany<ConferenceRoomRequest, $this>
     */
    public function conferenceRoomRequests(): HasMany
    {
        return $this->hasMany(ConferenceRoomRequest::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'iin' => 'encrypted',
            'role' => UserRole::class,
            'is_blocked' => 'boolean',
            'rules_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
