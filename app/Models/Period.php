<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use Database\Factories\PeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'start_date', 'end_date', 'status'])]
class Period extends Model
{
    /** @use HasFactory<PeriodFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type'       => PeriodType::class,
        'status'     => PeriodStatus::class,
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function lapses(): HasMany
    {
        return $this->hasMany(Lapse::class)->orderBy('number');
    }
}
