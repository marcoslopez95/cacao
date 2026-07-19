<?php

use App\Models\Catalogs\KinshipType;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Period;
use App\Models\Student;
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

test('guardian can enroll their student from the dashboard cta', function () {
    Period::factory()->active()->create();
    $pensum = Pensum::factory()->create();
    $student = Student::factory()->create([
        'current_pensum_id' => $pensum->id,
        'academic_year' => 1,
    ]);
    $guardian = Guardian::factory()->create();
    $kinship = KinshipType::firstOrCreate(['code' => 'other'], ['name' => 'Otro', 'active' => true, 'sort_order' => 99]);

    $student->guardians()->attach($guardian->id, [
        'kinship_type_id' => $kinship->id,
        'is_primary' => true,
        'is_emergency_contact' => false,
    ]);

    $this->browse(function ($browser) use ($guardian, $student) {
        $browser->loginAs($guardian->user)
            ->visit('/guardian/dashboard')
            ->waitForText($student->user->name, 10)
            ->assertSee($student->user->name)
            ->click('[dusk="guardian-enroll-btn"]')
            ->waitForText('Inscripción de materias', 10)
            ->assertPathIs('/enrollment')
            ->assertSee($student->user->name);
    });
});
