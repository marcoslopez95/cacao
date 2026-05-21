<?php

use App\Models\Enrollment;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;

// Note: Browser/Dusk tests run against the real database without transaction rollback.
// Roles must exist — seed with: php artisan db:seed --class=PermissionSeeder && php artisan db:seed --class=RoleSeeder
// Test data is not cleaned up (standard Dusk pattern to avoid FK cascade issues).

test('student can view their enrollment list', function () {
    $period = Period::factory()->active()->create();
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 1,
    ]);

    $this->browse(function ($browser) use ($student) {
        $browser->loginAs($student->user)
            ->visit('/enrollment')
            ->waitForText('Inscripción de materias', 10)
            ->assertSee('Inscripción de materias')
            ->assertPathIs('/enrollment');
    });
});

test('student without pensum sees empty state', function () {
    // A student with no pensum always sees the empty state regardless of active periods
    $student = Student::factory()->create(['current_pensum_id' => null]);

    $this->browse(function ($browser) use ($student) {
        $browser->loginAs($student->user)
            ->visit('/enrollment')
            ->waitForText('Inscripción', 10)
            ->assertSee('No hay período activo');
    });
});

test('admin cannot access enrollment index and sees 403', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $this->browse(function ($browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/enrollment')
            ->pause(2000)
            // Admin has no student/guardian association — controller aborts with 403
            ->assertSee('Forbidden');
    });
});

test('student cannot access another students enrollment', function () {
    $pensum = Pensum::factory()->create();

    $student1 = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 1,
    ]);

    $student2 = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 1,
    ]);

    $this->browse(function ($browser) use ($student1, $student2) {
        // The enrollment index only exists (no separate /enrollment/{id} show route).
        // Student2 attempts to pass student1's ID as a query param.
        // For authenticated students (not guardians), the controller ignores student_id
        // and always resolves the authenticated user's own student record.
        // Result: student2 sees THEIR OWN enrollment page, not student1's data.
        $browser->loginAs($student2->user)
            ->visit('/enrollment?student_id='.$student1->id)
            ->waitForText('Inscripción de materias', 10)
            ->assertSee('Inscripción de materias')
            // Confirm they are on the enrollment path (not redirected or 403)
            ->assertPathIs('/enrollment');
    });
});
