<?php

use App\Models\Catalogs\DocumentType;
use App\Models\User;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('user creation with first and last name produces correct generated name', function () {
    $user = User::factory()->create([
        'first_name' => 'Ana',
        'last_name' => 'López',
    ]);

    expect($user->fresh()->name)->toBe('Ana López');
});

test('uuid is auto-generated on create', function () {
    $user = User::factory()->create();

    expect($user->uuid)->not->toBeNull();
    expect($user->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('soft delete sets deleted_at and hides user from normal queries', function () {
    $user = User::factory()->create();
    $id = $user->id;

    $user->delete();

    expect(User::find($id))->toBeNull();
    expect(User::withTrashed()->find($id))->not->toBeNull();
    expect(User::withTrashed()->find($id)->deleted_at)->not->toBeNull();
});

test('document unique index rejects duplicate document type and number combination', function () {
    $this->seed(UserProfileCatalogsSeeder::class);

    $docTypeId = DocumentType::first()->id;

    User::factory()->create([
        'document_type_id' => $docTypeId,
        'document_number' => '12345678',
    ]);

    expect(fn () => User::factory()->create([
        'document_type_id' => $docTypeId,
        'document_number' => '12345678',
    ]))->toThrow(QueryException::class);
});
