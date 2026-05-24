<?php

namespace App\Models;

use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EmploymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Relation will be wired once departments table exists.
     *
     * @phpstan-ignore-next-line
     */
    public function coordinatedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'coordinated_department_id');
    }
}
