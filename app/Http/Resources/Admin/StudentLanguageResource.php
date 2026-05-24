<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentLanguageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => null,
            'student_id' => $this->student_id,
            'language_id' => $this->language_id,
            'language_level_id' => $this->language_level_id,
            'is_mother_tongue' => $this->is_mother_tongue,
            'language' => $this->whenLoaded('language', fn () => $this->language ? [
                'id' => $this->language->id,
                'code' => $this->language->code,
                'name' => $this->language->name,
            ] : null),
            'language_level' => $this->whenLoaded('languageLevel', fn () => $this->languageLevel ? [
                'id' => $this->languageLevel->id,
                'code' => $this->languageLevel->code,
                'name' => $this->languageLevel->name,
            ] : null),
        ];
    }
}
