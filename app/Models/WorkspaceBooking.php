<?php

namespace App\Models;

use App\Enums\WorkspaceBookingStatus;
use Database\Factories\WorkspaceBookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceBooking extends Model
{
    /** @use HasFactory<WorkspaceBookingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'workspace_number',
        'booking_date',
        'starts_at',
        'ends_at',
        'status',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'starts_at' => 'datetime:H:i',
            'ends_at' => 'datetime:H:i',
            'status' => WorkspaceBookingStatus::class,
        ];
    }
}
