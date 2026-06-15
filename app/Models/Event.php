<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'event_time',
        'banner_path',
    ];

    public function getBannerUrlAttribute(): ?string
    {
        if (blank($this->banner_path)) {
            return null;
        }

        if (Str::startsWith($this->banner_path, ['http://', 'https://'])) {
            return $this->banner_path;
        }

        return Storage::disk('public')->url($this->banner_path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'event_time' => 'datetime:H:i',
        ];
    }
}
