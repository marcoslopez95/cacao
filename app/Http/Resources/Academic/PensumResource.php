<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PensumResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'periodType' => $this->period_type,
            'totalPeriods' => $this->total_periods,
            'isActive' => $this->is_active,
            'subjectsCount' => $this->subjects_count ?? 0,
        ];
    }
}
