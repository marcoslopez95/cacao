<?php

use Illuminate\Support\Facades\Schema;

it('creates the class_sessions table with the expected columns', function () {
    expect(Schema::hasTable('class_sessions'))->toBeTrue();

    expect(Schema::hasColumns('class_sessions', [
        'id',
        'section_id',
        'uploaded_by_id',
        'linked_session_id',
        'type',
        'status',
        'professor_present',
        'topic',
        'held_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('creates the attendance_records table with the expected columns', function () {
    expect(Schema::hasTable('attendance_records'))->toBeTrue();

    expect(Schema::hasColumns('attendance_records', [
        'id',
        'class_session_id',
        'enrollment_detail_id',
        'status',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('has the correct foreign keys on class_sessions', function () {
    $fks = collect(Schema::getForeignKeys('class_sessions'));

    $sectionFk = $fks->first(fn ($fk) => in_array('section_id', $fk['columns']));
    expect($sectionFk)->not->toBeNull();
    expect($sectionFk['foreign_table'])->toBe('sections');

    $linkedFk = $fks->first(fn ($fk) => in_array('linked_session_id', $fk['columns']));
    expect($linkedFk)->not->toBeNull();
    expect($linkedFk['foreign_table'])->toBe('class_sessions');
});

it('has the correct foreign keys on attendance_records', function () {
    $fks = collect(Schema::getForeignKeys('attendance_records'));

    $sessionFk = $fks->first(fn ($fk) => in_array('class_session_id', $fk['columns']));
    expect($sessionFk)->not->toBeNull();
    expect($sessionFk['foreign_table'])->toBe('class_sessions');

    $detailFk = $fks->first(fn ($fk) => in_array('enrollment_detail_id', $fk['columns']));
    expect($detailFk)->not->toBeNull();
    expect($detailFk['foreign_table'])->toBe('enrollment_details');
});

it('enforces the unique constraint on attendance_records (class_session_id, enrollment_detail_id)', function () {
    $indexes = collect(Schema::getIndexes('attendance_records'));

    $hasUnique = $indexes->contains(function (array $index) {
        $columns = $index['columns'];

        return in_array('class_session_id', $columns)
            && in_array('enrollment_detail_id', $columns)
            && $index['unique'] === true;
    });

    expect($hasUnique)->toBeTrue();
});
