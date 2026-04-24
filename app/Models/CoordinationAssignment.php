<?php

namespace App\Models;

use Database\Factories\CoordinationAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['coordination_id', 'user_id', 'assigned_by', 'assigned_at', 'ended_at'])]
class CoordinationAssignment extends Model
{
    /** @use HasFactory<CoordinationAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function coordination(): BelongsTo
    {
        return $this->belongsTo(Coordination::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
