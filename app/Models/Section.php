<?php

namespace App\Models;

use App\Enums\SectionType;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['type', 'period_id', 'pensum_id', 'subject_id', 'code', 'grade', 'letter', 'theory_classroom_id', 'lab_classroom_id', 'main_teacher_id', 'classroom_id', 'capacity'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type'  => SectionType::class,
            'grade' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function pensum(): BelongsTo
    {
        return $this->belongsTo(Pensum::class);
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

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function mainTeacher(): BelongsTo
    {
        return $this->belongsTo(Professor::class, 'main_teacher_id');
    }

    public function sectionSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'section_subjects');
    }
}
