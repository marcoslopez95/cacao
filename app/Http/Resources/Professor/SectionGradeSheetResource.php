<?php

namespace App\Http\Resources\Professor;

use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionGradeSheetResource extends JsonResource
{
    public function __construct(
        private readonly Section $section,
        private readonly GradeConfig $config,
        private readonly ?int $lapseId,
    ) {
        parent::__construct($section);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $slots = $this->config->slots;
        $enrollmentDetails = $this->section->enrollmentDetails()
            ->with(['enrollment.student.user', 'gradeEntries.children'])
            ->get();

        $students = $enrollmentDetails->map(function ($detail) use ($slots) {
            $entriesBySlot = [];
            foreach ($slots as $slot) {
                $entry = $detail->gradeEntries
                    ->where('grade_slot_id', $slot->id)
                    ->where('lapse_id', $this->lapseId)
                    ->whereNull('parent_id')
                    ->first();

                $entriesBySlot[$slot->id] = $entry ? $this->formatEntry($entry) : null;
            }

            return [
                'enrollment_detail_id' => $detail->id,
                'name' => $detail->enrollment->student->user->name,
                'entries_by_slot' => $entriesBySlot,
            ];
        });

        return [
            'section_id' => $this->section->id,
            'section_code' => $this->section->code,
            'subject_name' => $this->section->subject->name,
            'lapse_id' => $this->lapseId,
            'passing_value' => (float) $this->config->passing_value,
            'slots' => $slots->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'weight' => $s->weight,
                'sort_order' => $s->sort_order,
                'is_remedial' => $s->is_remedial,
            ]),
            'students' => $students,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEntry(GradeEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'enrollment_detail_id' => $entry->enrollment_detail_id,
            'grade_slot_id' => $entry->grade_slot_id,
            'lapse_id' => $entry->lapse_id,
            'parent_id' => $entry->parent_id,
            'name' => $entry->name,
            'weight' => $entry->weight,
            'value' => $entry->value,
            'is_published' => $entry->is_published,
            'children' => $entry->children->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'weight' => $c->weight,
                'value' => $c->value,
            ])->values()->all(),
        ];
    }
}
