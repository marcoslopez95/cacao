<?php

namespace App\Models;

use App\Models\Catalogs\InstitutionalBenefit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StudentBenefit extends Pivot
{
    protected $table = 'student_benefits';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'benefit_id',
        'is_active',
        'since',
        'until',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'since' => 'date',
            'until' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function benefit(): BelongsTo
    {
        return $this->belongsTo(InstitutionalBenefit::class, 'benefit_id');
    }
}
