<?php

use App\Models\AttendanceRecord;
use App\Models\ClassSession;
use App\Models\EnrollmentDetail;
use App\Models\Section;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// ClassSession model relations
// ---------------------------------------------------------------------------

test('ClassSession section() returns BelongsTo', function () {
    expect((new ClassSession)->section())->toBeInstanceOf(BelongsTo::class);
});

test('ClassSession uploadedBy() returns BelongsTo', function () {
    expect((new ClassSession)->uploadedBy())->toBeInstanceOf(BelongsTo::class);
});

test('ClassSession linkedSession() returns BelongsTo', function () {
    expect((new ClassSession)->linkedSession())->toBeInstanceOf(BelongsTo::class);
});

test('ClassSession linkedFrom() returns HasMany', function () {
    expect((new ClassSession)->linkedFrom())->toBeInstanceOf(HasMany::class);
});

test('ClassSession attendanceRecords() returns HasMany', function () {
    expect((new ClassSession)->attendanceRecords())->toBeInstanceOf(HasMany::class);
});

// ---------------------------------------------------------------------------
// AttendanceRecord model relations
// ---------------------------------------------------------------------------

test('AttendanceRecord classSession() returns BelongsTo', function () {
    expect((new AttendanceRecord)->classSession())->toBeInstanceOf(BelongsTo::class);
});

test('AttendanceRecord enrollmentDetail() returns BelongsTo', function () {
    expect((new AttendanceRecord)->enrollmentDetail())->toBeInstanceOf(BelongsTo::class);
});

// ---------------------------------------------------------------------------
// Inverse relations on existing models
// ---------------------------------------------------------------------------

test('Section classSessions() returns HasMany', function () {
    expect((new Section)->classSessions())->toBeInstanceOf(HasMany::class);
});

test('EnrollmentDetail attendanceRecords() returns HasMany', function () {
    expect((new EnrollmentDetail)->attendanceRecords())->toBeInstanceOf(HasMany::class);
});
