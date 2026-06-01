<?php

namespace App\Http\Requests\Scheduling;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Enums\SectionType;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Services\Scheduling\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Schedule::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'professor_id' => ['required', 'integer', 'exists:professors,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'day_of_week' => ['required', 'string', Rule::enum(DayOfWeek::class)],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:07:00'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time', 'before_or_equal:18:00'],
            'type' => ['required', 'string', Rule::enum(ScheduleSessionType::class)],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->any()) {
                return;
            }

            $section = Section::with(['period', 'subject'])->find($this->integer('section_id'));
            if (! $section) {
                return;
            }

            $this->validateSubjectConsistency($v, $section);
            $this->validateProfessorConsistency($v, $section);
            $this->validateDateRange($v, $section);
            $this->validateConflicts($v);
        });
    }

    private function validateSubjectConsistency(Validator $v, Section $section): void
    {
        $subjectId = $this->integer('subject_id');

        if ($section->type === SectionType::University) {
            if ($section->subject_id !== $subjectId) {
                $v->errors()->add('subject_id', 'La materia no corresponde a esta sección universitaria.');
            }
        } else {
            $exists = $section->sectionSubjects()->where('subjects.id', $subjectId)->exists();
            if (! $exists) {
                $v->errors()->add('subject_id', 'La materia no pertenece al pensum de esta sección escolar.');
            }
        }
    }

    private function validateProfessorConsistency(Validator $v, Section $section): void
    {
        if ($section->type !== SectionType::School) {
            return;
        }

        $professorId = $this->integer('professor_id');
        $mainTeacherId = $section->main_teacher_id;

        if ($mainTeacherId !== null && $professorId !== $mainTeacherId) {
            $v->errors()->add('professor_id', 'El profesor debe ser el docente de aula asignado a esta sección.');
        }
    }

    private function validateDateRange(Validator $v, Section $section): void
    {
        $period = $section->period;
        $validFrom = $this->date('valid_from');

        if ($validFrom && $validFrom < $period->start_date) {
            $v->errors()->add('valid_from', "La fecha de inicio no puede ser anterior al inicio del período ({$period->start_date->toDateString()}).");
        }

        $validUntil = $this->date('valid_until');
        if ($validUntil && $validUntil > $period->end_date) {
            $v->errors()->add('valid_until', "La fecha de fin no puede superar el fin del período ({$period->end_date->toDateString()}).");
        }
    }

    private function validateConflicts(Validator $v): void
    {
        $service = app(ScheduleConflictService::class);

        // Build a transient (unsaved) Schedule to pass into the service
        $candidate = new Schedule([
            'section_id' => $this->integer('section_id'),
            'professor_id' => $this->integer('professor_id'),
            'classroom_id' => $this->integer('classroom_id'),
            'subject_id' => $this->integer('subject_id'),
            'day_of_week' => DayOfWeek::from($this->input('day_of_week')),
            'start_time' => $this->input('start_time'),
            'end_time' => $this->input('end_time'),
            'valid_from' => $this->input('valid_from'),
            'valid_until' => $this->input('valid_until'),
        ]);
        // Attach the section relation so the service can access section->period->end_date
        $candidate->setRelation('section', Section::with('period')->find($this->integer('section_id')));

        $classroomConflict = $service->classroomConflict($candidate);
        if ($classroomConflict) {
            $v->errors()->add('classroom_id', "El aula {$classroomConflict->classroom->identifier} ya tiene clase el {$classroomConflict->day_of_week->label()} de {$this->formatTime($classroomConflict->start_time)}–{$this->formatTime($classroomConflict->end_time)}.");
        }

        $professorConflict = $service->professorConflict($candidate);
        if ($professorConflict) {
            $v->errors()->add('professor_id', "El profesor {$professorConflict->professor->user->name} ya tiene clase el {$professorConflict->day_of_week->label()} de {$this->formatTime($professorConflict->start_time)}–{$this->formatTime($professorConflict->end_time)}.");
        }

        $weeklyExceeded = $service->professorWeeklyHoursExceeded($candidate);
        if ($weeklyExceeded) {
            $professor = Professor::with('user')->find($this->integer('professor_id'));
            $current = $service->professorCurrentWeeklyHours($professor);
            $newMinutes = Carbon::parse($this->input('start_time'))->diffInMinutes(Carbon::parse($this->input('end_time')));
            $newHours = round($newMinutes / 60, 1);
            $v->errors()->add('professor_id', "El profesor {$professor->user->name} superaría su límite de {$professor->weekly_hour_limit}h/semana ({$current}h actuales + {$newHours}h nuevas).");
        }
    }

    private function formatTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
