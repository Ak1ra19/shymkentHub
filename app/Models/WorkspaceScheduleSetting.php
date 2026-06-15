<?php

namespace App\Models;

use Database\Factories\WorkspaceScheduleSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['starts_on', 'starts_at', 'ends_at', 'note'])]
class WorkspaceScheduleSetting extends Model
{
    /** @use HasFactory<WorkspaceScheduleSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'starts_at' => 'datetime:H:i',
            'ends_at' => 'datetime:H:i',
        ];
    }
}
