<?php

use App\Enums\GradeLevel;
use App\Models\GradeConfig;
use App\Models\GradeEntry;
use App\Models\GradeSlot;
use App\Models\Period;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
});

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

test('unauthenticated users are redirected from grade configs index', function () {
    $this->get(route('security.grade-configs.index'))->assertRedirect(route('login'));
});

test('non-admin cannot access grade configs index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('security.grade-configs.index'))->assertForbidden();
});

test('admin can view grade configs index', function () {
    GradeConfig::factory()->university()->create();

    $this->actingAs(adminUser())
        ->get(route('security.grade-configs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/GradeConfigs/Index')
            ->has('configs')
            ->has('levels')
            ->has('can')
        );
});

// ---------------------------------------------------------------------------
// create / store
// ---------------------------------------------------------------------------

test('admin can view grade config create form', function () {
    $this->actingAs(adminUser())
        ->get(route('security.grade-configs.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/GradeConfigs/Form'));
});

test('admin can store a valid numeric grade config', function () {
    $this->actingAs(adminUser())
        ->post(route('security.grade-configs.store'), [
            'level' => 'university',
            'scale_type' => 'numeric',
            'scale_min' => 0,
            'scale_max' => 20,
            'passing_value' => 10,
            'slots' => [
                ['name' => 'Primer Parcial', 'weight' => 30, 'sort_order' => 1, 'is_remedial' => false],
                ['name' => 'Segundo Parcial', 'weight' => 30, 'sort_order' => 2, 'is_remedial' => false],
                ['name' => 'Examen Final', 'weight' => 40, 'sort_order' => 3, 'is_remedial' => false],
            ],
        ])
        ->assertRedirect(route('security.grade-configs.index'));

    expect(GradeConfig::where('level', GradeLevel::University)->exists())->toBeTrue();
    expect(GradeSlot::count())->toBe(3);
});

test('store fails when non-remedial slot weights do not sum to 100', function () {
    $this->actingAs(adminUser())
        ->post(route('security.grade-configs.store'), [
            'level' => 'university',
            'scale_type' => 'numeric',
            'scale_min' => 0,
            'scale_max' => 20,
            'passing_value' => 10,
            'slots' => [
                ['name' => 'Primer Parcial', 'weight' => 40, 'sort_order' => 1, 'is_remedial' => false],
                ['name' => 'Segundo Parcial', 'weight' => 40, 'sort_order' => 2, 'is_remedial' => false],
            ],
        ])
        ->assertSessionHasErrors('slots');
});

test('store creates remedial slot separately and validates only regular slot weights', function () {
    $this->actingAs(adminUser())
        ->post(route('security.grade-configs.store'), [
            'level' => 'university',
            'scale_type' => 'numeric',
            'scale_min' => 0,
            'scale_max' => 20,
            'passing_value' => 10,
            'slots' => [
                ['name' => 'Parcial', 'weight' => 100, 'sort_order' => 1, 'is_remedial' => false],
                ['name' => 'Reparación', 'weight' => 0, 'sort_order' => 2, 'is_remedial' => true],
            ],
        ])
        ->assertRedirect(route('security.grade-configs.index'));

    expect(GradeSlot::where('is_remedial', true)->exists())->toBeTrue();
});

test('store requires letter_values when scale type is letter', function () {
    $response = $this->actingAs(adminUser())
        ->post(route('security.grade-configs.store'), [
            'level' => 'primary_secondary',
            'scale_type' => 'letter',
            'passing_value' => 60,
            'slots' => [
                ['name' => 'Evaluación', 'weight' => 100, 'sort_order' => 1, 'is_remedial' => false],
            ],
        ]);

    $response->assertSessionHasErrors('letter_values');
});

// ---------------------------------------------------------------------------
// edit / update
// ---------------------------------------------------------------------------

test('admin can view grade config edit form', function () {
    $config = GradeConfig::factory()->university()->create();

    $this->actingAs(adminUser())
        ->get(route('security.grade-configs.edit', $config))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/GradeConfigs/Form'));
});

test('admin can update a grade config without existing entries', function () {
    $config = GradeConfig::factory()->university()->create();
    GradeSlot::factory()->create(['grade_config_id' => $config->id, 'weight' => 100]);

    $this->actingAs(adminUser())
        ->patch(route('security.grade-configs.update', $config), [
            'scale_type' => 'numeric',
            'scale_min' => 0,
            'scale_max' => 20,
            'passing_value' => 12,
            'slots' => [
                ['name' => 'Único', 'weight' => 100, 'sort_order' => 1, 'is_remedial' => false],
            ],
        ])
        ->assertRedirect(route('security.grade-configs.index'));

    expect($config->fresh()->passing_value)->toBe('12.00');
});

test('update with existing entries creates a period override instead of modifying in place', function () {
    $config = GradeConfig::factory()->university()->create();
    $slot = GradeSlot::factory()->create(['grade_config_id' => $config->id, 'weight' => 100]);
    $period = Period::factory()->active()->create();

    // Simulate existing grade entries
    GradeEntry::factory()->create(['grade_slot_id' => $slot->id]);

    $this->actingAs(adminUser())
        ->patch(route('security.grade-configs.update', $config), [
            'period_id' => $period->id,
            'scale_type' => 'numeric',
            'scale_min' => 0,
            'scale_max' => 20,
            'passing_value' => 15,
            'slots' => [
                ['name' => 'Único', 'weight' => 100, 'sort_order' => 1, 'is_remedial' => false],
            ],
        ])
        ->assertRedirect(route('security.grade-configs.index'));

    // A new period-specific config is created; the original is unchanged
    expect(GradeConfig::where('period_id', $period->id)->exists())->toBeTrue();
    expect($config->fresh()->passing_value)->not->toBe('15.00');
});
