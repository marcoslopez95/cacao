<?php

namespace App\Models;

use App\Enums\ClassroomType;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['building_id', 'identifier', 'type', 'capacity'])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type' => ClassroomType::class,
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
