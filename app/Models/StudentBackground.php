<?php

namespace App\Models;

use App\Models\Catalogs\DigitalLevel;
use App\Models\Catalogs\EducationLevel;
use App\Models\Catalogs\InstitutionType;
use App\Models\Catalogs\TransferReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBackground extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'previous_institution',
        'institution_type_id',
        'graduation_year',
        'previous_gpa',
        'repeated_grade',
        'repeated_grade_description',
        'transfer_reason_id',
        'has_prior_studies',
        'prior_studies_description',
        'digital_level_id',
        'mother_education_level_id',
        'father_education_level_id',
    ];

    protected function casts(): array
    {
        return [
            'previous_gpa' => 'decimal:2',
            'graduation_year' => 'integer',
            'repeated_grade' => 'boolean',
            'has_prior_studies' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class);
    }

    public function transferReason(): BelongsTo
    {
        return $this->belongsTo(TransferReason::class);
    }

    public function digitalLevel(): BelongsTo
    {
        return $this->belongsTo(DigitalLevel::class);
    }

    public function motherEducationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'mother_education_level_id');
    }

    public function fatherEducationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'father_education_level_id');
    }
}
