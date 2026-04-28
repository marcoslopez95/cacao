<?php

namespace App\Models;

use Database\Factories\LapseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['period_id', 'number', 'name', 'start_date', 'end_date'])]
class Lapse extends Model
{
    /** @use HasFactory<LapseFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
