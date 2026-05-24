<?php

namespace App\Models;

use App\Models\Catalogs\EmploymentType;
use App\Models\Catalogs\IncomeRange;
use App\Models\Catalogs\IncomeSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocioeconomicProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'income_range_id',
        'income_source_id',
        'household_earners',
        'receives_remittances',
        'remittance_country_id',
        'student_works',
        'employment_type_id',
        'weekly_work_hours',
        'has_scholarship',
        'scholarship_name',
        'has_institutional_benefit',
        'recorded_by',
        'study_date',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'receives_remittances' => 'boolean',
        'student_works' => 'boolean',
        'has_scholarship' => 'boolean',
        'has_institutional_benefit' => 'boolean',
        'study_date' => 'date',
        'household_earners' => 'integer',
        'weekly_work_hours' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function incomeRange(): BelongsTo
    {
        return $this->belongsTo(IncomeRange::class);
    }

    public function incomeSource(): BelongsTo
    {
        return $this->belongsTo(IncomeSource::class);
    }

    public function remittanceCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'remittance_country_id');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
