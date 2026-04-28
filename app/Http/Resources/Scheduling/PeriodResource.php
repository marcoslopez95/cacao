<?php

namespace App\Http\Resources\Scheduling;

use App\Enums\PeriodType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'type'        => $this->type->value,
            'typeLabel'   => $this->type->label(),
            'startDate'   => $this->start_date->toDateString(),
            'endDate'     => $this->end_date->toDateString(),
            'status'      => $this->status->value,
            'statusLabel' => $this->status->label(),
            'lapses'      => $this->type === PeriodType::Year
                ? LapseResource::collection($this->lapses)->resolve()
                : [],
        ];
    }
}
