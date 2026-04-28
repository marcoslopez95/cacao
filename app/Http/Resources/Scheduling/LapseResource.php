<?php

namespace App\Http\Resources\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LapseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'number'    => $this->number,
            'name'      => $this->name,
            'startDate' => $this->start_date->toDateString(),
            'endDate'   => $this->end_date->toDateString(),
        ];
    }
}
