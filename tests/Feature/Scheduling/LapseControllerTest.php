<?php

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use App\Models\Lapse;
use App\Models\Period;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['lapses.create', 'lapses.update', 'lapses.delete'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    // periods.view is needed for PeriodResource assertions
    Permission::firstOrCreate(['name' => 'periods.view', 'guard_name' => 'web']);
});

function userWithLapsePerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ---------------------------------------------------------------------------
// store
// ---------------------------------------------------------------------------

test('admin can create a lapse in a year period', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Primer Lapso',
            'start_date' => '2025-09-01',
            'end_date'   => '2025-11-30',
        ])
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Lapse::where('period_id', $period->id)->where('number', 1)->exists())->toBeTrue();
});

test('cannot create lapse in semester period', function () {
    $period = Period::factory()->semester()->create();

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => $period->start_date->toDateString(),
            'end_date'   => $period->start_date->copy()->addMonth()->toDateString(),
        ])
        ->assertSessionHasErrors('period_id');
});

test('cannot create lapse in trimester period', function () {
    $period = Period::factory()->trimester()->create();

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => $period->start_date->toDateString(),
            'end_date'   => $period->start_date->copy()->addMonth()->toDateString(),
        ])
        ->assertSessionHasErrors('period_id');
});

test('cannot create lapse in a closed period', function () {
    $period = Period::factory()->year()->closed()->create([
        'start_date' => '2024-09-01',
        'end_date'   => '2025-07-15',
    ]);

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2024-09-01',
            'end_date'   => '2024-11-30',
        ])
        ->assertSessionHasErrors('period_id');
});

test('lapse dates must be within period range', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    // start_date before period start_date
    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2025-08-01',
            'end_date'   => '2025-11-30',
        ])
        ->assertSessionHasErrors('start_date');
});

test('lapse end date must be within period range', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    // end_date after period end_date
    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2025-09-01',
            'end_date'   => '2026-08-01',
        ])
        ->assertSessionHasErrors('end_date');
});

test('lapse end date must be after start date', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2025-11-30',
            'end_date'   => '2025-09-01',
        ])
        ->assertSessionHasErrors('end_date');
});

test('cannot duplicate lapse number in same period', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    Lapse::factory()->forPeriod($period, 1)->create();

    $this->actingAs(userWithLapsePerm('lapses.create'))
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Otro Lapso',
            'start_date' => '2025-12-01',
            'end_date'   => '2026-02-28',
        ])
        ->assertSessionHasErrors('number');
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

test('admin can update a lapse', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);
    $lapse = Lapse::factory()->forPeriod($period, 1)->create();

    $this->actingAs(userWithLapsePerm('lapses.update'))
        ->patch("/scheduling/periods/{$period->id}/lapses/{$lapse->id}", [
            'number'     => 1,
            'name'       => 'Primer Lapso Actualizado',
            'start_date' => '2025-09-01',
            'end_date'   => '2025-11-30',
        ])
        ->assertRedirect(route('scheduling.periods.index'));

    expect($lapse->fresh()->name)->toBe('Primer Lapso Actualizado');
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

test('admin can delete a lapse', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);
    $lapse = Lapse::factory()->forPeriod($period, 1)->create();

    $this->actingAs(userWithLapsePerm('lapses.delete'))
        ->delete("/scheduling/periods/{$period->id}/lapses/{$lapse->id}")
        ->assertRedirect(route('scheduling.periods.index'));

    expect(Lapse::find($lapse->id))->toBeNull();
});

// ---------------------------------------------------------------------------
// authorization
// ---------------------------------------------------------------------------

test('user without permission cannot create lapse', function () {
    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);

    $this->actingAs(User::factory()->create())
        ->post("/scheduling/periods/{$period->id}/lapses", [
            'number'     => 1,
            'name'       => 'Lapso 1',
            'start_date' => '2025-09-01',
            'end_date'   => '2025-11-30',
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// PeriodResource includes lapses
// ---------------------------------------------------------------------------

test('period resource includes lapses for year periods', function () {
    Permission::firstOrCreate(['name' => 'periods.view', 'guard_name' => 'web']);

    $period = Period::factory()->year()->create([
        'start_date' => '2025-09-01',
        'end_date'   => '2026-07-15',
    ]);
    Lapse::factory()->forPeriod($period, 1)->create();
    Lapse::factory()->forPeriod($period, 2)->create();

    $user = User::factory()->create();
    $user->givePermissionTo('periods.view');

    $this->actingAs($user)
        ->get('/scheduling/periods')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('periods', 1)
            ->has('periods.0.lapses', 2)
            ->where('periods.0.lapses.0.number', 1)
            ->where('periods.0.lapses.1.number', 2)
        );
});

test('period resource excludes lapses for semester periods', function () {
    Permission::firstOrCreate(['name' => 'periods.view', 'guard_name' => 'web']);

    $period = Period::factory()->semester()->create();

    $user = User::factory()->create();
    $user->givePermissionTo('periods.view');

    $this->actingAs($user)
        ->get('/scheduling/periods')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('periods', 1)
            ->where('periods.0.lapses', [])
        );
});
