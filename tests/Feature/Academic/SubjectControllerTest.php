<?php

use App\Models\Career;
use App\Models\Pensum;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'subjects.view',                'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'subjects.create',              'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'subjects.update',              'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'subjects.delete',              'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'subjects.manage-prerequisites', 'guard_name' => 'web']);

    $this->career = Career::factory()->create(['code' => 'ING']);
    $this->pensum = Pensum::factory()->create([
        'career_id' => $this->career->id,
        'total_periods' => 10,
    ]);
});

function subjectUserWith(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login on subjects index', function () {
    $this->get(route('academic.subjects.index', ['career' => $this->career, 'pensum' => $this->pensum]))
        ->assertRedirect('/login');
});

test('authenticated user without subjects.view gets 403 on subjects index', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('academic.subjects.index', ['career' => $this->career, 'pensum' => $this->pensum]))
        ->assertForbidden();
});

test('user with subjects.view sees the subjects index page with subjects from this pensum', function () {
    Subject::factory()->count(2)->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);

    $otherPensum = Pensum::factory()->create(['career_id' => $this->career->id]);
    Subject::factory()->create(['pensum_id' => $otherPensum->id, 'period_number' => 1]);

    $this->actingAs(subjectUserWith('subjects.view'))
        ->get(route('academic.subjects.index', ['career' => $this->career, 'pensum' => $this->pensum]))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('academic/Subjects/Index', false)
                ->has('career')
                ->has('pensum')
                ->has('subjects', 2)
                ->has('can')
        );
});

test('subject of another pensum returns 404 on update via scopeBindings', function () {
    $otherPensum = Pensum::factory()->create(['career_id' => $this->career->id]);
    $subjectOther = Subject::factory()->create(['pensum_id' => $otherPensum->id, 'period_number' => 1]);

    $this->actingAs(subjectUserWith('subjects.update'))
        ->patch(route('academic.subjects.update', [
            'career' => $this->career,
            'pensum' => $this->pensum,
            'subject' => $subjectOther,
        ]), [
            'name' => 'Nombre nuevo',
            'code' => $subjectOther->code,
            'credits_uc' => 3,
            'period_number' => 1,
        ])
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('user without subjects.create gets 403 on store', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('academic.subjects.store', ['career' => $this->career, 'pensum' => $this->pensum]), [
            'name' => 'Cálculo I',
            'credits_uc' => 4,
            'period_number' => 1,
        ])
        ->assertForbidden();
});

test('store fails validation when name is empty', function () {
    $this->actingAs(subjectUserWith('subjects.create'))
        ->post(route('academic.subjects.store', ['career' => $this->career, 'pensum' => $this->pensum]), [
            'name' => '',
            'credits_uc' => 4,
            'period_number' => 1,
        ])
        ->assertSessionHasErrors('name');
});

test('store fails validation when credits_uc is zero', function () {
    $this->actingAs(subjectUserWith('subjects.create'))
        ->post(route('academic.subjects.store', ['career' => $this->career, 'pensum' => $this->pensum]), [
            'name' => 'Cálculo I',
            'credits_uc' => 0,
            'period_number' => 1,
        ])
        ->assertSessionHasErrors('credits_uc');
});

test('store fails validation when credits_uc exceeds 20', function () {
    $this->actingAs(subjectUserWith('subjects.create'))
        ->post(route('academic.subjects.store', ['career' => $this->career, 'pensum' => $this->pensum]), [
            'name' => 'Cálculo I',
            'credits_uc' => 21,
            'period_number' => 1,
        ])
        ->assertSessionHasErrors('credits_uc');
});

test('store fails validation when period_number is zero', function () {
    $this->actingAs(subjectUserWith('subjects.create'))
        ->post(route('academic.subjects.store', ['career' => $this->career, 'pensum' => $this->pensum]), [
            'name' => 'Cálculo I',
            'credits_uc' => 4,
            'period_number' => 0,
        ])
        ->assertSessionHasErrors('period_number');
});

test('store fails validation when period_number exceeds pensum total_periods', function () {
    $this->actingAs(subjectUserWith('subjects.create'))
        ->post(route('academic.subjects.store', ['career' => $this->career, 'pensum' => $this->pensum]), [
            'name' => 'Cálculo I',
            'credits_uc' => 4,
            'period_number' => 11, // pensum has total_periods = 10
        ])
        ->assertSessionHasErrors('period_number');
});

test('successful store creates subject with correct code format and redirects', function () {
    $this->actingAs(subjectUserWith('subjects.create'))
        ->post(route('academic.subjects.store', ['career' => $this->career, 'pensum' => $this->pensum]), [
            'name' => 'Cálculo I',
            'credits_uc' => 4,
            'period_number' => 1,
        ])
        ->assertRedirect(route('academic.subjects.index', ['career' => $this->career, 'pensum' => $this->pensum]));

    $subject = Subject::where('name', 'Cálculo I')->first();

    expect($subject)->not->toBeNull()
        ->and($subject->pensum_id)->toBe($this->pensum->id)
        ->and($subject->credits_uc)->toBe(4)
        ->and($subject->period_number)->toBe(1)
        ->and($subject->code)->toMatch('/^[A-Z]+-\d+\d{2}$/');
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('user without subjects.update gets 403 on update', function () {
    $subject = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);

    $this->actingAs(User::factory()->create())
        ->patch(route('academic.subjects.update', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subject]), [
            'name' => 'Nuevo nombre',
            'code' => $subject->code,
            'credits_uc' => 3,
            'period_number' => 1,
        ])
        ->assertForbidden();
});

test('successful update changes subject fields in database', function () {
    $subject = Subject::factory()->create([
        'pensum_id' => $this->pensum->id,
        'period_number' => 1,
        'credits_uc' => 3,
    ]);

    $this->actingAs(subjectUserWith('subjects.update'))
        ->patch(route('academic.subjects.update', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subject]), [
            'name' => 'Nombre actualizado',
            'code' => $subject->code,
            'credits_uc' => 5,
            'period_number' => 2,
        ])
        ->assertRedirect(route('academic.subjects.index', ['career' => $this->career, 'pensum' => $this->pensum]));

    $updated = $subject->fresh();
    expect($updated->name)->toBe('Nombre actualizado')
        ->and($updated->credits_uc)->toBe(5)
        ->and($updated->period_number)->toBe(2);
});

test('update fails validation when code is duplicated within same pensum', function () {
    $subjectA = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'code' => 'ING-101', 'period_number' => 1]);
    $subjectB = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'code' => 'ING-102', 'period_number' => 1]);

    $this->actingAs(subjectUserWith('subjects.update'))
        ->patch(route('academic.subjects.update', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subjectB]), [
            'name' => $subjectB->name,
            'code' => 'ING-101', // duplicate of subjectA
            'credits_uc' => $subjectB->credits_uc,
            'period_number' => $subjectB->period_number,
        ])
        ->assertSessionHasErrors('code');
});

test('update fails validation when period_number change leaves incompatible prerequisites', function () {
    $prereq = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);
    $subject = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 2]);
    $subject->prerequisites()->attach($prereq->id);

    // Try to move subject from period 2 → period 1; prereq is also in period 1 → incompatible
    $this->actingAs(subjectUserWith('subjects.update'))
        ->patch(route('academic.subjects.update', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subject]), [
            'name' => $subject->name,
            'code' => $subject->code,
            'credits_uc' => $subject->credits_uc,
            'period_number' => 1,
        ])
        ->assertSessionHasErrors('period_number');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('user without subjects.delete gets 403 on destroy', function () {
    $subject = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);

    $this->actingAs(User::factory()->create())
        ->delete(route('academic.subjects.destroy', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subject]))
        ->assertForbidden();
});

test('successful destroy deletes the subject and redirects', function () {
    $subject = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);

    $this->actingAs(subjectUserWith('subjects.delete'))
        ->delete(route('academic.subjects.destroy', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subject]))
        ->assertRedirect(route('academic.subjects.index', ['career' => $this->career, 'pensum' => $this->pensum]));

    expect(Subject::find($subject->id))->toBeNull();
});

test('destroy is blocked when subject is prerequisite of another', function () {
    $subjectA = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);
    $subjectB = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 2]);
    $subjectB->prerequisites()->attach($subjectA->id);

    $this->actingAs(subjectUserWith('subjects.delete'))
        ->delete(route('academic.subjects.destroy', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subjectA]))
        ->assertRedirect();

    expect(Subject::find($subjectA->id))->not->toBeNull();
});

test('subject from another pensum returns 404 on destroy via scopeBindings', function () {
    $otherPensum = Pensum::factory()->create(['career_id' => $this->career->id]);
    $subjectOther = Subject::factory()->create(['pensum_id' => $otherPensum->id, 'period_number' => 1]);

    $this->actingAs(subjectUserWith('subjects.delete'))
        ->delete(route('academic.subjects.destroy', [
            'career' => $this->career,
            'pensum' => $this->pensum,
            'subject' => $subjectOther,
        ]))
        ->assertNotFound();

    expect(Subject::find($subjectOther->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// syncPrerequisites
// ---------------------------------------------------------------------------

test('user without subjects.manage-prerequisites gets 403 on sync', function () {
    $subject = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 2]);

    $this->actingAs(User::factory()->create())
        ->post(route('academic.subjects.prerequisites.sync', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subject]), [
            'prerequisites' => [],
        ])
        ->assertForbidden();
});

test('successful sync replaces prerequisites', function () {
    $subjectA = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);
    $subjectB = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);
    $subjectD = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 2]);

    // Set initial prerequisite to subjectA only
    $subjectD->prerequisites()->sync([$subjectA->id]);

    // Now sync to [subjectA, subjectB]
    $this->actingAs(subjectUserWith('subjects.manage-prerequisites'))
        ->post(route('academic.subjects.prerequisites.sync', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subjectD]), [
            'prerequisites' => [$subjectA->id, $subjectB->id],
        ])
        ->assertRedirect(route('academic.subjects.index', ['career' => $this->career, 'pensum' => $this->pensum]));

    expect($subjectD->fresh()->prerequisites()->count())->toBe(2);
});

test('sync fails when a prerequisite belongs to another pensum', function () {
    $subject = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 2]);

    $otherPensum = Pensum::factory()->create(['career_id' => $this->career->id]);
    $subjectX = Subject::factory()->create(['pensum_id' => $otherPensum->id, 'period_number' => 1]);

    $this->actingAs(subjectUserWith('subjects.manage-prerequisites'))
        ->post(route('academic.subjects.prerequisites.sync', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subject]), [
            'prerequisites' => [$subjectX->id],
        ])
        ->assertSessionHasErrors('prerequisites');
});

test('sync fails when prerequisite has same period_number as subject', function () {
    $subjectA = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);
    $subjectB = Subject::factory()->create(['pensum_id' => $this->pensum->id, 'period_number' => 1]);

    // Try to set subjectA's prerequisites to [subjectB] — same period
    $this->actingAs(subjectUserWith('subjects.manage-prerequisites'))
        ->post(route('academic.subjects.prerequisites.sync', ['career' => $this->career, 'pensum' => $this->pensum, 'subject' => $subjectA]), [
            'prerequisites' => [$subjectB->id],
        ])
        ->assertSessionHasErrors('prerequisites');
});

test('subject from another pensum returns 404 on sync via scopeBindings', function () {
    $otherPensum = Pensum::factory()->create(['career_id' => $this->career->id]);
    $subjectOther = Subject::factory()->create(['pensum_id' => $otherPensum->id, 'period_number' => 2]);

    $this->actingAs(subjectUserWith('subjects.manage-prerequisites'))
        ->post(route('academic.subjects.prerequisites.sync', [
            'career' => $this->career,
            'pensum' => $this->pensum,
            'subject' => $subjectOther,
        ]), [
            'prerequisites' => [],
        ])
        ->assertNotFound();
});
