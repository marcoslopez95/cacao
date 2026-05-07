<?php

use App\Enums\SectionType;
use App\Models\Pensum;
use App\Models\Period;
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

function userWithSchoolPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

function yearPensumWithGrade(int $grade = 3): array
{
    $period  = Period::factory()->year()->create();
    $pensum  = Pensum::factory()->create(['period_type' => 'year', 'total_periods' => 6]);
    $subject = Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => $grade]);

    return [$period, $pensum, $subject];
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('admin can list school sections', function () {
    $period = Period::factory()->year()->create();
    $pensum = Pensum::factory()->create(['period_type' => 'year', 'total_periods' => 6]);

    foreach (['A', 'B', 'C'] as $letter) {
        Section::factory()->forPensumAndGrade($pensum, 3, $letter)->create(['period_id' => $period->id]);
    }

    $this->actingAs(userWithSchoolPerm('sections.view'))
        ->get('/scheduling/sections/school')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scheduling/Sections/School', false)
            ->has('sections', 3)
        );
});

test('unauthenticated user cannot access school sections', function () {
    $this->get('/scheduling/sections/school')->assertRedirect('/login');
});

test('user without permission cannot list school sections', function () {
    $this->actingAs(User::factory()->create())
        ->get('/scheduling/sections/school')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a school section', function () {
    [$period, $pensum] = yearPensumWithGrade(4);

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 4,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertRedirect(route('scheduling.sections.school.index'));

    $section = Section::where('type', SectionType::School)
        ->where('period_id', $period->id)
        ->where('pensum_id', $pensum->id)
        ->where('grade', 4)
        ->where('letter', 'A')
        ->first();

    expect($section)->not->toBeNull();
    expect($section->code)->toBe('4A');
});

test('creating a school section auto-populates section_subjects for the grade', function () {
    [$period, $pensum, $subject] = yearPensumWithGrade(3);
    Subject::factory()->create(['pensum_id' => $pensum->id, 'period_number' => 5]);

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 3,
            'letter'    => 'B',
            'capacity'  => 30,
        ])
        ->assertRedirect(route('scheduling.sections.school.index'));

    $section = Section::where('grade', 3)->where('letter', 'B')->first();

    expect($section->sectionSubjects)->toHaveCount(1);
    expect($section->sectionSubjects->first()->id)->toBe($subject->id);
});

test('cannot create school section with non-year period', function () {
    $period = Period::factory()->semester()->create();
    $pensum = Pensum::factory()->create(['period_type' => 'semester', 'total_periods' => 5]);

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 3,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertSessionHasErrors('period_id');
});

test('cannot create duplicate school section for same period pensum grade letter', function () {
    [$period, $pensum] = yearPensumWithGrade(2);
    Section::factory()->forPensumAndGrade($pensum, 2, 'A')->create(['period_id' => $period->id]);

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 2,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertSessionHasErrors('letter');
});

test('grade must be between 1 and 12', function () {
    [$period, $pensum] = yearPensumWithGrade();

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 0,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertSessionHasErrors('grade');
});

test('capacity must be at least 1 for school section', function () {
    [$period, $pensum] = yearPensumWithGrade();

    $this->actingAs(userWithSchoolPerm('sections.create'))
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 3,
            'letter'    => 'A',
            'capacity'  => 0,
        ])
        ->assertSessionHasErrors('capacity');
});

test('user without permission cannot create school section', function () {
    [$period, $pensum] = yearPensumWithGrade();

    $this->actingAs(User::factory()->create())
        ->post('/scheduling/sections/school', [
            'period_id' => $period->id,
            'pensum_id' => $pensum->id,
            'grade'     => 3,
            'letter'    => 'A',
            'capacity'  => 25,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update a school section letter and capacity', function () {
    [$period, $pensum] = yearPensumWithGrade();
    $section = Section::factory()->forPensumAndGrade($pensum, 3, 'A')->create(['period_id' => $period->id]);

    $this->actingAs(userWithSchoolPerm('sections.update'))
        ->patch("/scheduling/sections/school/{$section->id}", [
            'letter'   => 'B',
            'capacity' => 35,
        ])
        ->assertRedirect(route('scheduling.sections.school.index'));

    $section->refresh();
    expect($section->letter)->toBe('B');
    expect($section->capacity)->toBe(35);
    expect($section->code)->toBe('3B');
});

test('user without permission cannot update school section', function () {
    [$period, $pensum] = yearPensumWithGrade();
    $section = Section::factory()->forPensumAndGrade($pensum, 3)->create(['period_id' => $period->id]);

    $this->actingAs(User::factory()->create())
        ->patch("/scheduling/sections/school/{$section->id}", [
            'letter'   => 'C',
            'capacity' => 30,
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete a school section', function () {
    [$period, $pensum] = yearPensumWithGrade(3);
    $section = Section::factory()->forPensumAndGrade($pensum, 3)->create(['period_id' => $period->id]);

    $this->actingAs(userWithSchoolPerm('sections.delete'))
        ->delete("/scheduling/sections/school/{$section->id}")
        ->assertRedirect(route('scheduling.sections.school.index'));

    expect(Section::find($section->id))->toBeNull();
});

test('user without permission cannot delete school section', function () {
    [$period, $pensum] = yearPensumWithGrade();
    $section = Section::factory()->forPensumAndGrade($pensum, 3)->create(['period_id' => $period->id]);

    $this->actingAs(User::factory()->create())
        ->delete("/scheduling/sections/school/{$section->id}")
        ->assertForbidden();
});
