<?php

use App\Enums\EnrollmentDetailStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Catalogs\KinshipType;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Index
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login', function () {
    $this->withoutVite();

    $this->get('/enrollment')
        ->assertRedirect('/login');
});

test('authenticated student (unverified) can access enrollment page', function () {
    $this->withoutVite();

    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('enrollment/Index'));
});

test('authenticated student (verified) can access enrollment page', function () {
    $this->withoutVite();

    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('enrollment/Index'));
});

// NOTE: This test depends on BuildEnrollmentCatalogAction (Task 2) and
// EnrollmentCatalogSubjectResource (Task 3) which are not yet merged in this
// worktree. It will be enabled once those tasks land.
todo('student index returns enrollment, catalog, rules and can props when active period exists');

test('student index returns null enrollment when no active period', function () {
    $this->withoutVite();

    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/Index')
            ->where('enrollment', null)
            ->where('catalog', [])
        );
});

test('guardian can access enrollment index for assigned student', function () {
    $this->withoutVite();

    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);
    $student = Student::factory()->create();
    $student->guardians()->attach($guardian->id, ['kinship_type_id' => $kinship->id, 'is_primary' => true, 'is_emergency_contact' => false]);

    $this->actingAs($guardian->user)
        ->get('/enrollment')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('enrollment/Index')
            ->has('rules')
            ->has('can')
        );
});

// ---------------------------------------------------------------------------
// Store
// ---------------------------------------------------------------------------

test('student can create enrollment for active period', function () {
    Period::factory()->active()->create();

    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id]);

    $this->actingAs($student->user)
        ->postJson(route('enrollment.store'))
        ->assertCreated()
        ->assertJsonPath('status', EnrollmentStatus::Draft->value);

    $this->assertDatabaseHas('enrollments', [
        'student_id' => $student->id,
        'pensum_id' => $pensum->id,
        'status' => 'draft',
    ]);
});

test('guardian can create enrollment for assigned student', function () {
    Period::factory()->active()->create();

    $pensum = Pensum::factory()->create();
    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Outro', 'active' => true, 'sort_order' => 99]);
    $student = Student::factory()->create(['current_pensum_id' => $pensum->id]);
    $student->guardians()->attach($guardian->id, ['kinship_type_id' => $kinship->id, 'is_primary' => true, 'is_emergency_contact' => false]);

    $this->actingAs($guardian->user)
        ->postJson(route('enrollment.store'), ['student_id' => $student->id])
        ->assertCreated();

    $this->assertDatabaseHas('enrollments', ['student_id' => $student->id]);
});

test('guardian cannot create enrollment for unassigned student', function () {
    $guardian = Guardian::factory()->create();
    $stranger = Student::factory()->create();

    $this->actingAs($guardian->user)
        ->postJson(route('enrollment.store'), ['student_id' => $stranger->id])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Add detail
// ---------------------------------------------------------------------------

test('student can add subject to their draft enrollment', function () {
    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id, 'capacity' => 30]);
    $student = Student::factory()->create(['current_pensum_id' => $subject->pensum_id]);
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'pensum_id' => $subject->pensum_id,
    ]);

    $this->actingAs($student->user)
        ->postJson(route('enrollment.detail.store', $enrollment), [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('enrollment_details', [
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'status' => 'draft',
    ]);
});

test('adding same subject and section again is idempotent and returns existing detail', function () {
    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id, 'capacity' => 30]);
    $student = Student::factory()->create(['current_pensum_id' => $subject->pensum_id]);
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'pensum_id' => $subject->pensum_id,
    ]);

    $existing = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
    ]);

    $this->actingAs($student->user)
        ->postJson(route('enrollment.detail.store', $enrollment), [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
        ])
        ->assertOk()
        ->assertJsonFragment(['id' => $existing->id]);
});

test('student can change section for an enrolled subject', function () {
    $subject = Subject::factory()->create();
    $section1 = Section::factory()->create(['subject_id' => $subject->id, 'capacity' => 30]);
    $section2 = Section::factory()->create(['subject_id' => $subject->id, 'capacity' => 30]);
    $student = Student::factory()->create(['current_pensum_id' => $subject->pensum_id]);
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'pensum_id' => $subject->pensum_id,
    ]);

    $detail = EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section1->id,
    ]);

    $this->actingAs($student->user)
        ->postJson(route('enrollment.detail.store', $enrollment), [
            'subject_id' => $subject->id,
            'section_id' => $section2->id,
        ])
        ->assertOk()
        ->assertJsonFragment(['section_id' => $section2->id]);

    // Same row updated in-place (unique constraint on enrollment_id+subject_id)
    expect($detail->fresh()->section_id)->toBe($section2->id);
});

test('student cannot add subject to another students enrollment', function () {
    $owner = Student::factory()->create();
    $intruder = Student::factory()->create();

    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id]);
    $enrollment = Enrollment::factory()->create(['student_id' => $owner->id]);

    $this->actingAs($intruder->user)
        ->postJson(route('enrollment.detail.store', $enrollment), [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
        ])
        ->assertForbidden();
});

test('student cannot add subject when section has no quota', function () {
    $subject = Subject::factory()->create();
    $section = Section::factory()->create(['subject_id' => $subject->id, 'capacity' => 1]);
    $student = Student::factory()->create(['current_pensum_id' => $subject->pensum_id]);
    $enrollment = Enrollment::factory()->create([
        'student_id' => $student->id,
        'pensum_id' => $subject->pensum_id,
    ]);

    // Fill the only spot
    EnrollmentDetail::factory()->confirmed()->create([
        'subject_id' => $subject->id,
        'section_id' => $section->id,
    ]);

    $this->actingAs($student->user)
        ->postJson(route('enrollment.detail.store', $enrollment), [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['error' => 'No hay cupos disponibles en esta sección.']);
});

// ---------------------------------------------------------------------------
// Remove detail
// ---------------------------------------------------------------------------

test('student can remove a draft detail from their enrollment', function () {
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);
    $detail = EnrollmentDetail::factory()->create(['enrollment_id' => $enrollment->id]);

    $this->actingAs($student->user)
        ->deleteJson(route('enrollment.detail.destroy', [$enrollment, $detail]))
        ->assertSuccessful();

    $this->assertDatabaseHas('enrollment_details', [
        'id' => $detail->id,
        'status' => 'rejected',
    ]);
});

test('student cannot remove detail from confirmed enrollment', function () {
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->confirmed()->create(['student_id' => $student->id]);
    $detail = EnrollmentDetail::factory()->confirmed()->create(['enrollment_id' => $enrollment->id]);

    $this->actingAs($student->user)
        ->deleteJson(route('enrollment.detail.destroy', [$enrollment, $detail]))
        ->assertForbidden();
});

test('student cannot remove detail belonging to another student', function () {
    $owner = Student::factory()->create();
    $intruder = Student::factory()->create();

    $enrollment = Enrollment::factory()->create(['student_id' => $owner->id]);
    $detail = EnrollmentDetail::factory()->create(['enrollment_id' => $enrollment->id]);

    $this->actingAs($intruder->user)
        ->deleteJson(route('enrollment.detail.destroy', [$enrollment, $detail]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Confirm
// ---------------------------------------------------------------------------

test('student can confirm draft enrollment with details', function () {
    $subject = Subject::factory()->create(['credits_uc' => 3]);
    $section = Section::factory()->create(['subject_id' => $subject->id, 'capacity' => 30]);
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);

    EnrollmentDetail::factory()->create([
        'enrollment_id' => $enrollment->id,
        'subject_id' => $subject->id,
        'section_id' => $section->id,
        'status' => EnrollmentDetailStatus::Draft,
    ]);

    $this->actingAs($student->user)
        ->postJson(route('enrollment.confirm', $enrollment))
        ->assertSuccessful()
        ->assertJsonPath('status', EnrollmentStatus::Confirmed->value);

    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollment->id,
        'status' => 'confirmed',
    ]);
});

test('student cannot confirm empty enrollment', function () {
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->create(['student_id' => $student->id]);

    $this->actingAs($student->user)
        ->postJson(route('enrollment.confirm', $enrollment))
        ->assertUnprocessable()
        ->assertJsonFragment(['error' => 'La inscripción no tiene materias pendientes de confirmar.']);
});

test('student cannot confirm already confirmed enrollment', function () {
    $student = Student::factory()->create();
    $enrollment = Enrollment::factory()->confirmed()->create(['student_id' => $student->id]);

    $this->actingAs($student->user)
        ->postJson(route('enrollment.confirm', $enrollment))
        ->assertForbidden();
});
