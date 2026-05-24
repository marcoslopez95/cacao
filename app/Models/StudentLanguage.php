<?php

namespace App\Models;

use App\Models\Catalogs\Language;
use App\Models\Catalogs\LanguageLevel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StudentLanguage extends Pivot
{
    protected $table = 'student_languages';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'language_id',
        'language_level_id',
        'is_mother_tongue',
    ];

    protected function casts(): array
    {
        return [
            'is_mother_tongue' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function languageLevel(): BelongsTo
    {
        return $this->belongsTo(LanguageLevel::class);
    }
}
