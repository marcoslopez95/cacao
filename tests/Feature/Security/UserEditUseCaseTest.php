<?php

use App\Models\Catalogs\AttachmentDocumentType;
use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EducationLevel;
use App\Models\Catalogs\EmploymentStatus;
use App\Models\Catalogs\EmploymentType;
use App\Models\Catalogs\HouseholdHeadType;
use App\Models\Catalogs\HousingType;
use App\Models\Catalogs\IncomeRange;
use App\Models\Catalogs\IncomeSource;
use App\Models\Catalogs\InstitutionalBenefit;
use App\Models\Catalogs\InsuranceType;
use App\Models\Catalogs\Language;
use App\Models\Catalogs\LanguageLevel;
use App\Models\Catalogs\LivingArrangement;
use App\Models\Catalogs\MaritalStatus;
use App\Models\Catalogs\Religion;
use App\Models\Catalogs\TenureType;
use App\Models\Country;
use App\Models\DemographicProfile;
use App\Models\FamilyProfile;
use App\Models\Guardian;
use App\Models\GuardianProfile;
use App\Models\HealthProfile;
use App\Models\HousingProfile;
use App\Models\Professor;
use App\Models\SocioeconomicProfile;
use App\Models\StaffProfile;
use App\Models\State;
use App\Models\Student;
use App\Models\StudentBackground;
use App\Models\User;
use App\Models\UserConsent;
use App\Models\UserDocument;
use Database\Seeders\Catalogs\AcademicCatalogsSeeder;
use Database\Seeders\Catalogs\GeographicSeeder;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
use Database\Seeders\Catalogs\StaffCatalogsSeeder;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Storage::fake('local');

    Role::firstOrCreate(['name' => 'Admin',         'guard_name' => 'web']); // super-admin bypass
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante',    'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Profesor',      'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Representante', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Coordinador',   'guard_name' => 'web']);

    $this->seed(GeographicSeeder::class);
    $this->seed(SocialCatalogsSeeder::class);
    $this->seed(SocioeconomicCatalogsSeeder::class);
    $this->seed(UserProfileCatalogsSeeder::class);
    $this->seed(AcademicCatalogsSeeder::class);
    $this->seed(StaffCatalogsSeeder::class);
});

/**
 * Admin user with only the 'Admin' role — mirrors a real production admin account.
 * Gate::before in AppServiceProvider short-circuits all Gate::authorize() calls for this role.
 */
function adminForEditUseCase(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

/**
 * Creates an active consent record for the target user.
 * Required by health, socioeconomic, and housing profile endpoints.
 */
function createConsentForEditUseCase(User $user): UserConsent
{
    return UserConsent::create([
        'user_id' => $user->id,
        'policy_version' => 'v1.0',
        'accepts_data_processing' => true,
        'accepts_image_use' => true,
        'accepts_whatsapp_contact' => true,
        'accepts_email_contact' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test-agent',
        'granted_at' => now(),
        'revoked_at' => null,
    ]);
}

// ---------------------------------------------------------------------------
// Carga de la página de edición
// ---------------------------------------------------------------------------

test('edit page renders with base props for any user', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('security/Users/Edit', false)
            ->has('user')
            ->has('addresses')
            ->has('documents')
            ->has('consent')
        );
});

test('edit page includes student prop when user has a student record', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $student->user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('student'));
});

test('edit page includes professor prop when user has a professor record', function () {
    $admin = adminForEditUseCase();
    $professor = Professor::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $professor->user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('professor'));
});

test('edit page includes guardian prop when user has a guardian record', function () {
    $admin = adminForEditUseCase();
    $guardian = Guardian::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $guardian->user))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('guardian'));
});

test('edit page catalogData includes countries when geographic data is seeded', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('security.users.edit', $target))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('catalogData')
            ->where('catalogData.countries.0', fn ($value) => ! empty($value))
        );
});

// ---------------------------------------------------------------------------
// S1 — Identidad personal: PATCH /security/users/{user}
// ---------------------------------------------------------------------------

test('S1 identity: admin can update name, email and role', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('security.users.update', $target), [
            'first_name' => 'María',
            'last_name' => 'González',
            'email' => 'maria@test.com',
            'roles' => ['Profesor'],
        ])
        ->assertRedirect(route('security.users.index'));

    $target->refresh();
    expect($target->first_name)->toBe('María')
        ->and($target->last_name)->toBe('González')
        ->and($target->email)->toBe('maria@test.com')
        ->and($target->hasRole('Profesor'))->toBeTrue();
});

test('S1 identity: roles are replaced on each update', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();
    $target->assignRole('Profesor');

    $this->actingAs($admin)
        ->patch(route('security.users.update', $target), [
            'first_name' => $target->first_name,
            'last_name' => $target->last_name,
            'email' => $target->email,
            'roles' => ['Estudiante'],
        ])
        ->assertRedirect(route('security.users.index'));

    $target->refresh();
    expect($target->hasRole('Estudiante'))->toBeTrue()
        ->and($target->hasRole('Profesor'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// S3 — Dirección: POST /security/users/{user}/addresses
// ---------------------------------------------------------------------------

test('S3 address: admin can create a primary address', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();
    $venezuela = Country::where('iso2', 'VE')->first();
    $state = State::where('country_id', $venezuela->id)->first();

    $this->actingAs($admin)
        ->postJson(route('security.users.addresses.store', $target), [
            'country_id' => $venezuela->id,
            'state_id' => $state->id,
            'address_line1' => 'Av. Libertador, Torre Centro, Piso 3',
            'is_primary' => true,
        ])
        ->assertSuccessful();

    expect($target->addresses()->where('is_primary', true)->exists())->toBeTrue();
});

test('S3 address: admin can update an existing address', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();
    $venezuela = Country::where('iso2', 'VE')->first();
    $state = State::where('country_id', $venezuela->id)->first();

    $storeResponse = $this->actingAs($admin)->postJson(
        route('security.users.addresses.store', $target),
        [
            'country_id' => $venezuela->id,
            'state_id' => $state->id,
            'address_line1' => 'Calle Primera',
            'is_primary' => true,
        ]
    )->assertSuccessful();

    $addressId = $storeResponse->json('data.id');

    $this->actingAs($admin)
        ->putJson(route('security.users.addresses.update', [$target, $addressId]), [
            'country_id' => $venezuela->id,
            'state_id' => $state->id,
            'address_line1' => 'Calle Segunda',
            'is_primary' => true,
        ])
        ->assertSuccessful();

    expect($target->addresses()->where('address_line1', 'Calle Segunda')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// S4 — Perfil demográfico: PUT /security/users/{user}/demographic-profile
// ---------------------------------------------------------------------------

test('S4 demographic: admin can save demographic profile', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();
    $religion = Religion::first();

    $this->actingAs($admin)
        ->putJson(route('security.users.demographic-profile.upsert', $target), [
            'birth_city' => 'Caracas',
            'is_indigenous' => false,
            'religion_id' => $religion?->id,
        ])
        ->assertSuccessful();

    expect(DemographicProfile::where('user_id', $target->id)->exists())->toBeTrue();
});

test('S4 demographic: second save updates without creating a duplicate row', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();

    $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $target),
        ['birth_city' => 'Caracas']
    );

    $this->actingAs($admin)->putJson(
        route('security.users.demographic-profile.upsert', $target),
        ['birth_city' => 'Maracaibo']
    );

    expect(DemographicProfile::where('user_id', $target->id)->count())->toBe(1)
        ->and(DemographicProfile::where('user_id', $target->id)->first()->birth_city)->toBe('Maracaibo');
});

it('admin can save S04 demographic without nullifying religion_id', function () {
    // Admin with ONLY the 'Admin' role — no 'Administrador' alias.
    // This is the role used in production; hasAnyRole must include 'Admin' to grant
    // access to religion_id in DemographicProfileResource (regression for HLZ-14).
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $target = User::factory()->create();
    $religion = Religion::first();

    // Pre-create the demographic profile with a non-null religion_id.
    DemographicProfile::create([
        'user_id' => $target->id,
        'religion_id' => $religion->id,
    ]);

    $this->actingAs($admin)
        ->putJson(route('security.users.demographic-profile.upsert', $target), [
            'religion_id' => $religion->id,
        ])
        ->assertOk();

    expect(
        DemographicProfile::where('user_id', $target->id)->first()->religion_id
    )->not->toBeNull()
        ->toBe($religion->id);
});

// ---------------------------------------------------------------------------
// S5 — Perfil de salud: PUT /security/users/{user}/health-profile
// (requiere consentimiento activo)
// ---------------------------------------------------------------------------

test('S5 health: admin can save health profile', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();
    createConsentForEditUseCase($target);

    $this->actingAs($admin)
        ->putJson(route('security.users.health-profile.upsert', $target), [
            'weight_kg' => 68.0,
            'height_cm' => 172.0,
            'has_disability' => false,
        ])
        ->assertSuccessful();

    expect(HealthProfile::where('user_id', $target->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// S6 — Consentimiento: POST /security/users/{user}/consents
// ---------------------------------------------------------------------------

test('S6 consent: user can store their own consent', function () {
    // The consent endpoint requires the request user to match the route user —
    // consent must be given by the person themselves, not by an admin.
    $target = User::factory()->create();

    $this->actingAs($target)
        ->postJson(route('security.users.consents.store', $target), [
            'policy_version' => 'v1.0',
            'accepts_data_processing' => true,
            'accepts_image_use' => true,
            'accepts_whatsapp_contact' => false,
            'accepts_email_contact' => true,
        ])
        ->assertSuccessful();

    expect(
        UserConsent::where('user_id', $target->id)
            ->whereNull('revoked_at')
            ->exists()
    )->toBeTrue();
});

test('S6 consent: existing active consent can be revoked', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();
    $consent = createConsentForEditUseCase($target);

    $this->actingAs($admin)
        ->patch(route('security.users.consents.revoke', [$target, $consent]))
        ->assertSuccessful();

    expect($consent->fresh()->revoked_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// S7 — Documentos: POST /security/users/{user}/documents
// ---------------------------------------------------------------------------

test('S7 documents: admin can upload a document for user', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();
    $attachmentType = AttachmentDocumentType::first()->id;

    $this->actingAs($admin)
        ->postJson(route('security.users.documents.store', $target), [
            'attachment_type_id' => $attachmentType,
            'file' => UploadedFile::fake()->create('cedula.pdf', 100, 'application/pdf'),
        ])
        ->assertSuccessful();

    expect(UserDocument::where('user_id', $target->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// S8 — Antecedentes estudiantiles: PUT /security/students/{student}/background
// ---------------------------------------------------------------------------

test('S8 background: admin can save student academic background', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->putJson(route('security.students.background.upsert', $student), [
            'previous_institution' => 'U.E. Liceo Andrés Bello',
            'graduation_year' => 2021,
            'previous_gpa' => '16.75',
        ])
        ->assertSuccessful();

    expect(StudentBackground::where('student_id', $student->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// S10 — Idiomas: POST /security/students/{student}/languages
// ---------------------------------------------------------------------------

test('S10 languages: admin can add a language to student', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();
    $lang = Language::first();
    $level = LanguageLevel::first();

    $this->actingAs($admin)
        ->postJson(route('security.students.languages.store', $student), [
            'language_id' => $lang->id,
            'language_level_id' => $level->id,
            'is_mother_tongue' => false,
        ])
        ->assertSuccessful();

    expect(
        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $lang->id)
            ->exists()
    )->toBeTrue();
});

test('S10 languages: admin can remove a language from student', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();
    $lang = Language::first();
    $level = LanguageLevel::first();

    $this->actingAs($admin)->postJson(route('security.students.languages.store', $student), [
        'language_id' => $lang->id,
        'language_level_id' => $level->id,
        'is_mother_tongue' => false,
    ]);

    $this->actingAs($admin)
        ->deleteJson(route('security.students.languages.destroy', [$student, $lang]))
        ->assertSuccessful();

    expect(
        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $lang->id)
            ->exists()
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// S11 — Perfil familiar: PUT /security/students/{student}/family-profile
// ---------------------------------------------------------------------------

test('S11 family: admin can save family profile', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->putJson(route('security.students.family-profile.upsert', $student), [
            'sibling_count' => 3,
            'sibling_position' => 2,
        ])
        ->assertSuccessful();

    expect(FamilyProfile::where('student_id', $student->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// S12 — Perfil socioeconómico: PUT /security/students/{student}/socioeconomic-profile
// (requiere consentimiento activo)
// ---------------------------------------------------------------------------

test('S12 socioeconomic: admin can save socioeconomic profile', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();
    createConsentForEditUseCase($student->user);

    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'study_date' => '2024-01-15',
            'household_earners' => 2,
            'receives_remittances' => false,
        ])
        ->assertSuccessful();

    expect(SocioeconomicProfile::where('student_id', $student->id)->exists())->toBeTrue();
});

test('S12 socioeconomic: missing consent returns 422', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'household_earners' => 2,
        ])
        ->assertUnprocessable();
});

// ---------------------------------------------------------------------------
// S13 — Beneficios: POST /security/students/{student}/benefits/{benefit}
// ---------------------------------------------------------------------------

test('S13 benefits: admin can attach a benefit to student', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $this->actingAs($admin)
        ->postJson(route('security.students.benefits.store', [$student, $benefit]))
        ->assertSuccessful();

    expect(
        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists()
    )->toBeTrue();
});

test('S13 benefits: admin can detach a benefit from student', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $this->actingAs($admin)->postJson(
        route('security.students.benefits.store', [$student, $benefit])
    );

    $this->actingAs($admin)
        ->deleteJson(route('security.students.benefits.destroy', [$student, $benefit]))
        ->assertSuccessful();

    expect(
        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists()
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// S14 — Vivienda: PUT /security/students/{student}/housing-profile
// (requiere consentimiento activo)
// ---------------------------------------------------------------------------

test('S14 housing: admin can save housing profile', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();
    createConsentForEditUseCase($student->user);

    $this->actingAs($admin)
        ->putJson(route('security.students.housing-profile.upsert', $student), [
            'room_count' => 3,
            'household_members' => 4,
        ])
        ->assertSuccessful();

    expect(HousingProfile::where('student_id', $student->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// S16 — Perfil de representante: PUT /security/guardians/{guardian}/profile
// ---------------------------------------------------------------------------

test('S16 guardian profile: admin can save guardian profile', function () {
    $admin = adminForEditUseCase();
    $guardian = Guardian::factory()->create();

    $this->actingAs($admin)
        ->putJson(route('security.guardians.profile.upsert', $guardian), [
            'occupation' => 'Contador Público',
            'employer' => 'Empresa Nacional S.A.',
            'work_phone' => '+584121234567',
            'education_level_id' => EducationLevel::first()?->id,
            'marital_status_id' => MaritalStatus::first()?->id,
        ])
        ->assertSuccessful();

    expect(GuardianProfile::where('guardian_id', $guardian->id)->exists())->toBeTrue();
});

test('S16 guardian profile: second save updates without creating a duplicate row', function () {
    $admin = adminForEditUseCase();
    $guardian = Guardian::factory()->create();

    $this->actingAs($admin)->putJson(
        route('security.guardians.profile.upsert', $guardian),
        ['occupation' => 'Ingeniero']
    );

    $this->actingAs($admin)->putJson(
        route('security.guardians.profile.upsert', $guardian),
        ['occupation' => 'Arquitecto']
    );

    expect(GuardianProfile::where('guardian_id', $guardian->id)->count())->toBe(1)
        ->and(GuardianProfile::where('guardian_id', $guardian->id)->first()->occupation)->toBe('Arquitecto');
});

// ---------------------------------------------------------------------------
// S17 — Perfil de personal: PUT /academic/professors/{professor}/staff-profile
// ---------------------------------------------------------------------------

test('S17 staff profile: admin can save professor staff profile', function () {
    $admin = adminForEditUseCase();
    $professor = Professor::factory()->create();

    $this->actingAs($admin)
        ->putJson(route('academic.professors.staff-profile.upsert', $professor), [
            'contract_type_id' => ContractType::first()->id,
            'dedication_type_id' => DedicationType::first()->id,
            'employment_status_id' => EmploymentStatus::first()->id,
            'hire_date' => '2020-01-15',
            'is_coordinator' => false,
        ])
        ->assertSuccessful();

    expect(StaffProfile::where('professor_id', $professor->id)->exists())->toBeTrue();
});

test('S17 staff profile: second save updates without creating a duplicate row', function () {
    $admin = adminForEditUseCase();
    $professor = Professor::factory()->create();

    $payload = [
        'contract_type_id' => ContractType::first()->id,
        'dedication_type_id' => DedicationType::first()->id,
        'employment_status_id' => EmploymentStatus::first()->id,
        'hire_date' => '2020-01-15',
        'is_coordinator' => false,
    ];

    $this->actingAs($admin)->putJson(route('academic.professors.staff-profile.upsert', $professor), $payload);

    $this->actingAs($admin)->putJson(
        route('academic.professors.staff-profile.upsert', $professor),
        array_merge($payload, ['hire_date' => '2022-06-01'])
    );

    expect(StaffProfile::where('professor_id', $professor->id)->count())->toBe(1)
        ->and(StaffProfile::where('professor_id', $professor->id)->first()->hire_date->toDateString())->toBe('2022-06-01');
});

// ---------------------------------------------------------------------------
// Regression tests — HLZ-06: admin with only 'Admin' role (no 'Administrador')
// must not receive 403 on S10, S13, S16 endpoints.
// ---------------------------------------------------------------------------

test('S10 languages: admin con solo rol Admin puede agregar idioma (regression HLZ-06)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin'); // only 'Admin' — no 'Administrador' alias

    $student = Student::factory()->create();
    $lang = Language::first();
    $level = LanguageLevel::first();

    $this->actingAs($admin)
        ->postJson(route('security.students.languages.store', $student), [
            'language_id' => $lang->id,
            'language_level_id' => $level->id,
            'is_mother_tongue' => false,
        ])
        ->assertSuccessful(); // must NOT return 403

    expect(
        DB::table('student_languages')
            ->where('student_id', $student->id)
            ->where('language_id', $lang->id)
            ->exists()
    )->toBeTrue();
});

test('S13 benefits: admin con solo rol Admin puede adjuntar beneficio (regression HLZ-06)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin'); // only 'Admin' — no 'Administrador' alias

    $student = Student::factory()->create();
    $benefit = InstitutionalBenefit::first();

    $this->actingAs($admin)
        ->postJson(route('security.students.benefits.store', [$student, $benefit]))
        ->assertSuccessful(); // must NOT return 403

    expect(
        DB::table('student_benefits')
            ->where('student_id', $student->id)
            ->where('benefit_id', $benefit->id)
            ->exists()
    )->toBeTrue();
});

test('S16 guardian profile: admin con solo rol Admin puede guardar perfil de representante (regression HLZ-06)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin'); // only 'Admin' — no 'Administrador' alias

    $guardian = Guardian::factory()->create();

    $this->actingAs($admin)
        ->putJson(route('security.guardians.profile.upsert', $guardian), [
            'occupation' => 'Economista',
        ])
        ->assertSuccessful(); // must NOT return 403

    expect(GuardianProfile::where('guardian_id', $guardian->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// S5 — Perfil de salud con IDs de catálogo
// ---------------------------------------------------------------------------

it('saves health profile with catalog IDs and reloads them correctly', function () {
    $admin = adminForEditUseCase();
    $target = User::factory()->create();
    createConsentForEditUseCase($target);

    $bloodType = BloodType::first();
    $insuranceType = InsuranceType::first();

    $this->actingAs($admin)
        ->putJson(route('security.users.health-profile.upsert', $target), [
            'blood_type_id' => $bloodType->id,
            'has_medical_insurance' => true,
            'insurance_type_id' => $insuranceType->id,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('health_profiles', [
        'user_id' => $target->id,
        'blood_type_id' => $bloodType->id,
        'insurance_type_id' => $insuranceType->id,
    ]);
});

// ---------------------------------------------------------------------------
// S11 — Perfil familiar con IDs de catálogo
// ---------------------------------------------------------------------------

it('saves family profile with catalog IDs', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();

    $maritalStatus = MaritalStatus::first();
    $livingArrangement = LivingArrangement::first();
    $householdHeadType = HouseholdHeadType::first();

    $this->actingAs($admin)
        ->putJson(route('security.students.family-profile.upsert', $student), [
            'guardian_marital_status_id' => $maritalStatus->id,
            'living_arrangement_id' => $livingArrangement->id,
            'household_head_type_id' => $householdHeadType->id,
            'sibling_count' => 2,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('family_profiles', [
        'student_id' => $student->id,
        'guardian_marital_status_id' => $maritalStatus->id,
        'living_arrangement_id' => $livingArrangement->id,
        'household_head_type_id' => $householdHeadType->id,
    ]);
});

// ---------------------------------------------------------------------------
// S12 — Perfil socioeconómico con IDs de catálogo y normalización de study_date
// ---------------------------------------------------------------------------

it('saves socioeconomic profile with catalog IDs and normalizes study_date', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();
    createConsentForEditUseCase($student->user);

    $incomeRange = IncomeRange::first();
    $incomeSource = IncomeSource::first();
    $employmentType = EmploymentType::first();

    $this->actingAs($admin)
        ->putJson(route('security.students.socioeconomic-profile.upsert', $student), [
            'income_range_id' => $incomeRange->id,
            'income_source_id' => $incomeSource->id,
            'employment_type_id' => $employmentType->id,
            'study_date' => '2026-01-15',
            'receives_remittances' => false,
            'student_works' => true,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('socioeconomic_profiles', [
        'student_id' => $student->id,
        'income_range_id' => $incomeRange->id,
        'income_source_id' => $incomeSource->id,
        'employment_type_id' => $employmentType->id,
    ]);

    $profile = SocioeconomicProfile::where('student_id', $student->id)->first();
    expect($profile->study_date->toDateString())->toBe('2026-01-15');
});

// ---------------------------------------------------------------------------
// S14 — Perfil de vivienda con IDs de catálogo
// ---------------------------------------------------------------------------

it('saves housing profile with catalog IDs', function () {
    $admin = adminForEditUseCase();
    $student = Student::factory()->create();
    createConsentForEditUseCase($student->user);

    $housingType = HousingType::first();
    $tenureType = TenureType::first();

    $this->actingAs($admin)
        ->putJson(route('security.students.housing-profile.upsert', $student), [
            'housing_type_id' => $housingType->id,
            'tenure_type_id' => $tenureType->id,
            'room_count' => 3,
            'household_members' => 4,
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('housing_profiles', [
        'student_id' => $student->id,
        'housing_type_id' => $housingType->id,
        'tenure_type_id' => $tenureType->id,
    ]);
});
