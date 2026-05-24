<?php

namespace App\Models;

use App\Models\Catalogs\BasicService;
use App\Models\Catalogs\CommuteTime;
use App\Models\Catalogs\ConstructionMaterial;
use App\Models\Catalogs\HousingType;
use App\Models\Catalogs\TenureType;
use App\Models\Catalogs\TransportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HousingProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'housing_type_id',
        'tenure_type_id',
        'construction_material_id',
        'room_count',
        'bathroom_count',
        'household_members',
        'commute_time_id',
        'transport_type_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'room_count' => 'integer',
        'bathroom_count' => 'integer',
        'household_members' => 'integer',
        'is_overcrowded' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function housingType(): BelongsTo
    {
        return $this->belongsTo(HousingType::class);
    }

    public function tenureType(): BelongsTo
    {
        return $this->belongsTo(TenureType::class);
    }

    public function constructionMaterial(): BelongsTo
    {
        return $this->belongsTo(ConstructionMaterial::class);
    }

    public function commuteTime(): BelongsTo
    {
        return $this->belongsTo(CommuteTime::class);
    }

    public function transportType(): BelongsTo
    {
        return $this->belongsTo(TransportType::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(BasicService::class, 'housing_services', 'housing_profile_id', 'basic_service_id')
            ->withPivot(['is_available']);
    }
}
