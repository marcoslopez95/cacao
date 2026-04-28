<?php

use App\Enums\PeriodStatus;
use App\Models\Period;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (["periods.view", "periods.create", "periods.update", "periods.delete"] as $perm) {
        Permission::firstOrCreate(["name" => $perm, "guard_name" => "web"]);
    }
});

function userWithPeriodPerm(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    return $user;
}

test("admin can list periods", function () {
    Period::factory()->count(3)->create();
    $this->actingAs(userWithPeriodPerm("periods.view"))
        ->get("/scheduling/periods")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component("scheduling/Periods/Index", false)
            ->has("periods", 3)
        );
});

test("admin can filter periods by type", function () {
    Period::factory()->semester()->count(2)->create();
    Period::factory()->year()->count(1)->create();
    $this->actingAs(userWithPeriodPerm("periods.view"))
        ->get("/scheduling/periods?type=semester")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has("periods", 2));
});

test("unauthenticated user cannot list periods", function () {
    $this->get("/scheduling/periods")->assertRedirect("/login");
});

test("user without permission cannot list periods", function () {
    $this->actingAs(User::factory()->create())
        ->get("/scheduling/periods")
        ->assertForbidden();
});

test("admin can create a semester period", function () {
    $this->actingAs(userWithPeriodPerm("periods.create"))
        ->post("/scheduling/periods", [
            "name"       => "2026-1",
            "type"       => "semester",
            "start_date" => "2026-02-01",
            "end_date"   => "2026-07-31",
        ])
        ->assertRedirect(route("scheduling.periods.index"));
    expect(Period::where("name", "2026-1")->exists())->toBeTrue();
});

test("admin can create an annual period", function () {
    $this->actingAs(userWithPeriodPerm("periods.create"))
        ->post("/scheduling/periods", [
            "name"       => "2025-2026",
            "type"       => "year",
            "start_date" => "2025-09-01",
            "end_date"   => "2026-07-15",
        ])
        ->assertRedirect(route("scheduling.periods.index"));
    expect(Period::where("name", "2025-2026")->where("type", "year")->exists())->toBeTrue();
});

test("period name must be unique", function () {
    Period::factory()->create(["name" => "2026-1"]);
    $this->actingAs(userWithPeriodPerm("periods.create"))
        ->post("/scheduling/periods", [
            "name"       => "2026-1",
            "type"       => "semester",
            "start_date" => "2026-02-01",
            "end_date"   => "2026-07-31",
        ])
        ->assertSessionHasErrors("name");
    expect(Period::where("name", "2026-1")->count())->toBe(1);
});

test("end_date must be after start_date", function () {
    $this->actingAs(userWithPeriodPerm("periods.create"))
        ->post("/scheduling/periods", [
            "name"       => "2026-bad",
            "type"       => "semester",
            "start_date" => "2026-07-01",
            "end_date"   => "2026-01-01",
        ])
        ->assertSessionHasErrors("end_date");
});

test("invalid period type is rejected", function () {
    $this->actingAs(userWithPeriodPerm("periods.create"))
        ->post("/scheduling/periods", [
            "name"       => "2026-x",
            "type"       => "quarterly",
            "start_date" => "2026-01-01",
            "end_date"   => "2026-03-31",
        ])
        ->assertSessionHasErrors("type");
});

test("admin can update a period", function () {
    $period = Period::factory()->create(["name" => "original"]);
    $this->actingAs(userWithPeriodPerm("periods.update"))
        ->patch("/scheduling/periods/{$period->id}", [
            "name"       => "updated",
            "type"       => $period->type->value,
            "start_date" => $period->start_date->toDateString(),
            "end_date"   => $period->end_date->toDateString(),
        ])
        ->assertRedirect(route("scheduling.periods.index"));
    expect($period->fresh()->name)->toBe("updated");
});

test("admin can activate an upcoming period", function () {
    $period = Period::factory()->create(["status" => "upcoming"]);
    $this->actingAs(userWithPeriodPerm("periods.update"))
        ->patch("/scheduling/periods/{$period->id}/activate")
        ->assertRedirect(route("scheduling.periods.index"));
    expect($period->fresh()->status)->toBe(PeriodStatus::Active);
});

test("cannot activate an already active period", function () {
    $period = Period::factory()->active()->create();
    $this->actingAs(userWithPeriodPerm("periods.update"))
        ->patch("/scheduling/periods/{$period->id}/activate")
        ->assertRedirect(route("scheduling.periods.index"));
    expect($period->fresh()->status)->toBe(PeriodStatus::Active);
});

test("cannot activate a closed period", function () {
    $period = Period::factory()->closed()->create();
    $this->actingAs(userWithPeriodPerm("periods.update"))
        ->patch("/scheduling/periods/{$period->id}/activate")
        ->assertRedirect(route("scheduling.periods.index"));
    expect($period->fresh()->status)->toBe(PeriodStatus::Closed);
});

test("admin can close an active period", function () {
    $period = Period::factory()->active()->create();
    $this->actingAs(userWithPeriodPerm("periods.update"))
        ->patch("/scheduling/periods/{$period->id}/close")
        ->assertRedirect(route("scheduling.periods.index"));
    expect($period->fresh()->status)->toBe(PeriodStatus::Closed);
});

test("cannot close an upcoming period", function () {
    $period = Period::factory()->create(["status" => "upcoming"]);
    $this->actingAs(userWithPeriodPerm("periods.update"))
        ->patch("/scheduling/periods/{$period->id}/close")
        ->assertRedirect(route("scheduling.periods.index"));
    expect($period->fresh()->status)->toBe(PeriodStatus::Upcoming);
});

test("admin can delete an upcoming period", function () {
    $period = Period::factory()->create(["status" => "upcoming"]);
    $this->actingAs(userWithPeriodPerm("periods.delete"))
        ->delete("/scheduling/periods/{$period->id}")
        ->assertRedirect(route("scheduling.periods.index"));
    expect(Period::find($period->id))->toBeNull();
});

test("cannot delete an active period", function () {
    $period = Period::factory()->active()->create();
    $this->actingAs(userWithPeriodPerm("periods.delete"))
        ->delete("/scheduling/periods/{$period->id}")
        ->assertRedirect(route("scheduling.periods.index"));
    expect(Period::find($period->id))->not->toBeNull();
});

test("cannot delete a closed period", function () {
    $period = Period::factory()->closed()->create();
    $this->actingAs(userWithPeriodPerm("periods.delete"))
        ->delete("/scheduling/periods/{$period->id}")
        ->assertRedirect(route("scheduling.periods.index"));
    expect(Period::find($period->id))->not->toBeNull();
});
