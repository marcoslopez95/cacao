<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CareerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'active' => $this->active,
            'category' => [
                'id' => $this->careerCategory->id,
                'name' => $this->careerCategory->name,
            ],
            'pensumsCount' => $this->pensums_count ?? 0,
        ];
    }
}
