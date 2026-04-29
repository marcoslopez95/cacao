<?php

use App\Enums\SectionType;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Pensum;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['sections.view', 'sections.create', 'sections.update', 'sections.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
});

function userWithSectionPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

function semesterPeriodWithSubject(): array
{
    $period  = Period::factory()->semester()->create();
    $pensum  = Pensum::factory()->create(['period_type' => 'semester']);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id]);

    return [$period, $subject];
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list university sections', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    Section::factory()->forPeriodAndSubject($period, $subject)->count(3)->create();

    $this->actingAs(userWithSectionPerm('sections.view'))
        ->get('/scheduling/sections/university')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Sections/University', false)
            ->has('sections', 3)
        );
});

test('admin can filter sections by period', function () {
    [$period1, $subject1] = semesterPeriodWithSubject();
    [$period2, $subject2] = semesterPeriodWithSubject();

    Section::factory()->forPeriodAndSubject($period1, $subject1)->count(2)->create();
    Section::factory()->forPeriodAndSubject($period2, $subject2)->count(1)->create();

    $this->actingAs(userWithSectionPerm('sections.view'))
        ->get("/scheduling/sections/university?period_id={$period1->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('sections', 2));
});

test('unauthenticated user cannot access university sections', function () {
    $this->get('/scheduling/sections/university')->assertRedirect('/login');
});

test('user without permission cannot list sections', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/sections/university')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a university section', function () {
    [$period, $subject] = semesterPeriodWithSubject();

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 30,
        ])
        ->assertRedirect(route('scheduling.sections.university.index'));

    expect(Section::where('period_id', $period->id)
        ->where('subject_id', $subject->id)
        ->where('code', '01')
        ->exists()
    )->toBeTrue();
});

test('cannot create section with year period', function () {
    $period  = Period::factory()->year()->create();
    $pensum  = Pensum::factory()->create(['period_type' => 'year']);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id]);

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 30,
        ])
        ->assertSessionHasErrors('period_id');
});

test('cannot create section with mismatched pensum period type', function () {
    $period  = Period::factory()->semester()->create();
    $pensum  = Pensum::factory()->create(['period_type' => 'trimester']);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id]);

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 30,
        ])
        ->assertSessionHasErrors('subject_id');
});

test('cannot create duplicate section code for same period and subject', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    Section::factory()->forPeriodAndSubject($period, $subject)->create(['code' => '01']);

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 25,
        ])
        ->assertSessionHasErrors('code');
});

test('capacity must be at least 1', function () {
    [$period, $subject] = semesterPeriodWithSubject();

    $this->actingAs(userWithSectionPerm('sections.create'))
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 0,
        ])
        ->assertSessionHasErrors('capacity');
});

test('user without permission cannot create section', function () {
    [$period, $subject] = semesterPeriodWithSubject();

    $this->actingAs(User::factory()->create())
        ->post('/scheduling/sections/university', [
            'period_id'  => $period->id,
            'subject_id' => $subject->id,
            'code'       => '01',
            'capacity'   => 30,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update a section code and capacity', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    $section = Section::factory()->forPeriodAndSubject($period, $subject)->create(['code' => '01', 'capacity' => 30]);

    $this->actingAs(userWithSectionPerm('sections.update'))
        ->patch("/scheduling/sections/university/{$section->id}", [
            'code'     => '02',
            'capacity' => 40,
        ])
        ->assertRedirect(route('scheduling.sections.university.index'));

    expect($section->fresh()->code)->toBe('02');
    expect($section->fresh()->capacity)->toBe(40);
});

test('user without permission cannot update section', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    $section = Section::factory()->forPeriodAndSubject($period, $subject)->create();

    $this->actingAs(User::factory()->create())
        ->patch("/scheduling/sections/university/{$section->id}", [
            'code'     => '99',
            'capacity' => 10,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete a section', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    $section = Section::factory()->forPeriodAndSubject($period, $subject)->create();

    $this->actingAs(userWithSectionPerm('sections.delete'))
        ->delete("/scheduling/sections/university/{$section->id}")
        ->assertRedirect(route('scheduling.sections.university.index'));

    expect(Section::find($section->id))->toBeNull();
});

test('user without permission cannot delete section', function () {
    [$period, $subject] = semesterPeriodWithSubject();
    $section = Section::factory()->forPeriodAndSubject($period, $subject)->create();

    $this->actingAs(User::factory()->create())
        ->delete("/scheduling/sections/university/{$section->id}")
        ->assertForbidden();
});
