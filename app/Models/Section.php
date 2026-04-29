<?php

namespace App\Models;

use App\Enums\SectionType;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'period_id', 'subject_id', 'code', 'theory_classroom_id', 'lab_classroom_id', 'capacity'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function theoryClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'theory_classroom_id');
    }

    public function labClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'lab_classroom_id');
    }
}
