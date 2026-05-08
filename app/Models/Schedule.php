<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['section_id', 'professor_id', 'classroom_id', 'subject_id', 'day_of_week', 'start_time', 'end_time', 'type', 'valid_from', 'valid_until'])]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'type'        => ScheduleSessionType::class,
            'valid_from'  => 'date',
            'valid_until' => 'date',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
