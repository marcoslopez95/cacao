<?php

use App\Http\Resources\Scheduling\ScheduleResource;
use App\Models\Career;
use App\Models\Classroom;
use App\Models\Pensum;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;

it('includes career info when subject has a pensum with career', function (): void {
    $career  = Career::factory()->create(['name' => 'Ingeniería de Sistemas']);
    $pensum  = Pensum::factory()->for($career)->create();
    $subject = Subject::factory()->for($pensum)->create();
    $schedule = Schedule::factory()
        ->for(Section::factory())
        ->for(Professor::factory())
        ->for(Classroom::factory())
        ->for($subject)
        ->create();

    $resource = (new ScheduleResource(
        $schedule->load(['section', 'professor.user', 'classroom', 'subject.pensum.career'])
    ))->toArray(request());

    expect($resource['career'])->toBe(['id' => $career->id, 'name' => 'Ingeniería de Sistemas']);
});

it('returns null career when pensum is not loaded on subject', function (): void {
    $subject  = Subject::factory()->create();
    $schedule = Schedule::factory()
        ->for(Section::factory())
        ->for(Professor::factory())
        ->for(Classroom::factory())
        ->for($subject)
        ->create();

    // Load without subject.pensum.career so pensum is null on the subject instance
    $loaded = $schedule->load(['section', 'professor.user', 'classroom', 'subject']);
    $loaded->subject->setRelation('pensum', null);

    $resource = (new ScheduleResource($loaded))->toArray(request());

    expect($resource['career'])->toBeNull();
});
