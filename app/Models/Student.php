<?php

namespace App\Models;

use App\Enums\EducationalLevel;
use App\Models\Catalogs\AcademicShift;
use App\Models\Catalogs\AcademicStatus;
use App\Models\Catalogs\AdmissionType;
use App\Models\Catalogs\Language;
use App\Models\Catalogs\SchoolGrade;
use App\Models\Catalogs\StudyModality;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'educational_level',
        'current_pensum_id',
        'academic_year',
        'student_code',
        'academic_status_id',
        'modality_id',
        'shift_id',
        'admission_type_id',
        'cumulative_gpa',
        'grade_id',
        'enrollment_date',
    ];

    protected function casts(): array
    {
        return [
            'educational_level' => EducationalLevel::class,
            'academic_year' => 'integer',
            'cumulative_gpa' => 'decimal:2',
            'enrollment_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pensum(): BelongsTo
    {
        return $this->belongsTo(Pensum::class, 'current_pensum_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardians')
            ->withPivot(['kinship_type_id', 'is_primary', 'is_emergency_contact']);
    }

    public function primaryGuardian(): ?Guardian
    {
        return $this->guardians()->wherePivot('is_primary', true)->first();
    }

    public function academicStatus(): BelongsTo
    {
        return $this->belongsTo(AcademicStatus::class);
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(StudyModality::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(AcademicShift::class);
    }

    public function admissionType(): BelongsTo
    {
        return $this->belongsTo(AdmissionType::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(SchoolGrade::class);
    }

    public function background(): HasOne
    {
        return $this->hasOne(StudentBackground::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(
            Language::class,
            'student_languages',
            'student_id',
            'language_id'
        )->withPivot(['language_level_id', 'is_mother_tongue'])
            ->using(StudentLanguage::class);
    }

    public function familyProfile(): HasOne
    {
        return $this->hasOne(FamilyProfile::class);
    }
}
