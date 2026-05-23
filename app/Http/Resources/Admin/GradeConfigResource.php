<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level->value,
            'level_label' => $this->level->label(),
            'period_id' => $this->period_id,
            'scale_type' => $this->scale_type->value,
            'scale_min' => $this->scale_min,
            'scale_max' => $this->scale_max,
            'passing_value' => $this->passing_value,
            'slots' => $this->whenLoaded('slots', fn () => $this->slots->map(fn ($slot) => [
                'id' => $slot->id,
                'name' => $slot->name,
                'weight' => $slot->weight,
                'sort_order' => $slot->sort_order,
                'is_remedial' => $slot->is_remedial,
            ])),
            'letter_values' => $this->whenLoaded('letterValues', fn () => $this->letterValues->map(fn ($lv) => [
                'id' => $lv->id,
                'letter' => $lv->letter,
                'numeric_equiv' => $lv->numeric_equiv,
                'is_passing' => $lv->is_passing,
                'sort_order' => $lv->sort_order,
            ])),
        ];
    }
}
