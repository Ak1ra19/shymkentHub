<?php

namespace App\Models;

use App\Enums\ConferenceRoomRequestStatus;
use Database\Factories\ConferenceRoomRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenceRoomRequest extends Model
{
    /** @use HasFactory<ConferenceRoomRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_date',
        'starts_at',
        'ends_at',
        'purpose',
        'status',
        'admin_comment',
        'reviewed_at',
        'reviewed_by_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
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
            'status' => ConferenceRoomRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }
}
