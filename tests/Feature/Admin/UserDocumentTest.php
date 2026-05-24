<?php

use App\Models\Catalogs\AttachmentDocumentType;
use App\Models\User;
use App\Models\UserDocument;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    $this->seed(UserProfileCatalogsSeeder::class);
    Storage::fake('local');
});

function adminForDocument(): User
{
    $user = User::factory()->create();
    $user->assignRole('Administrador');

    return $user;
}

test('admin can upload a document with an accepted mime type', function () {
    $admin = adminForDocument();
    $target = User::factory()->create();
    $attachmentTypeId = AttachmentDocumentType::first()->id;

    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $response = $this->actingAs($admin)->postJson(
        route('security.users.documents.store', $target),
        [
            'attachment_type_id' => $attachmentTypeId,
            'file' => $file,
        ]
    );

    $response->assertSuccessful();
    expect(UserDocument::where('user_id', $target->id)->exists())->toBeTrue();
    expect(UserDocument::where('user_id', $target->id)->first()->mime_type)->toBe('application/pdf');
});

test('uploading a document with a rejected mime type returns 422', function () {
    $admin = adminForDocument();
    $target = User::factory()->create();
    $attachmentTypeId = AttachmentDocumentType::first()->id;

    $file = UploadedFile::fake()->create('doc.exe', 100, 'application/x-msdownload');

    $response = $this->actingAs($admin)->postJson(
        route('security.users.documents.store', $target),
        [
            'attachment_type_id' => $attachmentTypeId,
            'file' => $file,
        ]
    );

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['file']);
});

test('admin can verify a document belonging to another user', function () {
    $admin = adminForDocument();
    $target = User::factory()->create();
    $attachmentTypeId = AttachmentDocumentType::first()->id;

    $document = UserDocument::create([
        'user_id' => $target->id,
        'attachment_type_id' => $attachmentTypeId,
        'file_url' => 'documents/1/test.pdf',
        'original_filename' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'file_size_bytes' => 1024,
    ]);

    $response = $this->actingAs($admin)->patchJson(
        route('security.users.documents.verify', [$target, $document])
    );

    $response->assertSuccessful();
    expect($document->fresh()->is_verified)->toBeTrue();
});

test('user cannot self-verify their own document and receives 403', function () {
    $admin = adminForDocument();
    $attachmentTypeId = AttachmentDocumentType::first()->id;

    $document = UserDocument::create([
        'user_id' => $admin->id,
        'attachment_type_id' => $attachmentTypeId,
        'file_url' => 'documents/1/self.pdf',
        'original_filename' => 'self.pdf',
        'mime_type' => 'application/pdf',
        'file_size_bytes' => 512,
    ]);

    $response = $this->actingAs($admin)->patchJson(
        route('security.users.documents.verify', [$admin, $document])
    );

    $response->assertForbidden();
});
