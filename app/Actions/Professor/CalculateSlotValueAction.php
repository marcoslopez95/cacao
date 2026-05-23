<?php

namespace App\Actions\Professor;

use App\Models\GradeEntry;

class CalculateSlotValueAction
{
    public function handle(GradeEntry $parent): GradeEntry
    {
        $children = $parent->children()->whereNotNull('value')->get();

        if ($children->isEmpty()) {
            return $parent;
        }

        $calculated = $children->sum(
            fn (GradeEntry $child) => (float) $child->value * (float) $child->weight / 100
        );

        $parent->update(['value' => round($calculated, 2)]);

        return $parent->fresh();
    }
}
