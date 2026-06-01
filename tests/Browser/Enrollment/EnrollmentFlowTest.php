<?php

use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseMigrations::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

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
        $browser->loginAs($student2->user)
            ->visit('/enrollment?student_id='.$student1->id)
            ->waitForText('Inscripción de materias', 10)
            ->assertSee('Inscripción de materias')
            ->assertPathIs('/enrollment');
    });
});
