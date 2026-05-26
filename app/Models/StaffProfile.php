<?php

namespace App\Models;

use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Staff profile for a professor.
 *
 * FK: `staff_profiles.professor_id` → `professors.id`
 * To find a user's staff profile: User → Professor → StaffProfile
 * Do NOT use StaffProfile::where('user_id', ...) — no such column exists.
 */
class StaffProfile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'professor_id',
        'employee_code',
        'academic_title',
        'specialty',
        'contract_type_id',
        'dedication_type_id',
        'weekly_hour_load',
        'hire_date',
        'termination_date',
        'employment_status_id',
        'is_coordinator',
        'coordinated_department_id',
        'coordinator_since',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'termination_date' => 'date',
            'coordinator_since' => 'date',
            'is_coordinator' => 'boolean',
        ];
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function dedicationType(): BelongsTo
    {
        return $this->belongsTo(DedicationType::class);
    }

    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(EmploymentStatus::class);
    }

    /**
     * The coordination (department) this professor coordinates, if is_coordinator=true.
     * Uses the `coordinations` table (model Coordination), NOT a `departments` table.
     */
    public function coordinatedDepartment(): BelongsTo
    {
        return $this->belongsTo(Coordination::class, 'coordinated_department_id');
    }
}
