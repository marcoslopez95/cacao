<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['pensum_id', 'name', 'code', 'credits_uc', 'period_number', 'description'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    public function pensum(): BelongsTo
    {
        return $this->belongsTo(Pensum::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_prerequisites',
            'subject_id',
            'prerequisite_id',
        )->as('prerequisites');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_prerequisites',
            'prerequisite_id',
            'subject_id',
        )->as('dependents');
    }
}
