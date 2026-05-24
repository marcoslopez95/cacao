<?php

namespace App\Models;

use App\Models\Catalogs\HouseholdHeadType;
use App\Models\Catalogs\LivingArrangement;
use App\Models\Catalogs\MaritalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'guardian_marital_status_id',
        'children_count',
        'sibling_position',
        'sibling_count',
        'living_arrangement_id',
        'household_head_type_id',
        'household_head_name',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'children_count' => 'integer',
        'sibling_position' => 'integer',
        'sibling_count' => 'integer',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<MaritalStatus, $this> */
    public function guardianMaritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class, 'guardian_marital_status_id');
    }

    /** @return BelongsTo<LivingArrangement, $this> */
    public function livingArrangement(): BelongsTo
    {
        return $this->belongsTo(LivingArrangement::class);
    }

    /** @return BelongsTo<HouseholdHeadType, $this> */
    public function householdHeadType(): BelongsTo
    {
        return $this->belongsTo(HouseholdHeadType::class);
    }
}
