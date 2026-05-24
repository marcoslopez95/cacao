<?php

namespace App\Models;

use App\Models\Catalogs\EducationLevel;
use App\Models\Catalogs\MaritalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'guardian_id',
        'occupation',
        'employer',
        'work_phone',
        'education_level_id',
        'marital_status_id',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }
}
