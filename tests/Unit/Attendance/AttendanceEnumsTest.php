<?php

use App\Enums\AttendanceStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\ClassSessionType;

// ---------------------------------------------------------------------------
// ClassSessionType enum
// ---------------------------------------------------------------------------

test('ClassSessionType has correct values', function () {
    expect(ClassSessionType::Regular->value)->toBe('regular')
        ->and(ClassSessionType::Makeup->value)->toBe('makeup')
        ->and(ClassSessionType::Advance->value)->toBe('advance');
});

test('ClassSessionType labels are correct', function () {
    expect(ClassSessionType::Regular->label())->toBe('Regular')
        ->and(ClassSessionType::Makeup->label())->toBe('Recuperación')
        ->and(ClassSessionType::Advance->label())->toBe('Adelanto');
});

// ---------------------------------------------------------------------------
// ClassSessionStatus enum
// ---------------------------------------------------------------------------

test('ClassSessionStatus has correct values', function () {
    expect(ClassSessionStatus::Scheduled->value)->toBe('scheduled')
        ->and(ClassSessionStatus::Held->value)->toBe('held')
        ->and(ClassSessionStatus::Cancelled->value)->toBe('cancelled')
        ->and(ClassSessionStatus::Recovered->value)->toBe('recovered')
        ->and(ClassSessionStatus::Advanced->value)->toBe('advanced');
});

test('ClassSessionStatus labels are correct', function () {
    expect(ClassSessionStatus::Scheduled->label())->toBe('Pendiente')
        ->and(ClassSessionStatus::Held->label())->toBe('Dada')
        ->and(ClassSessionStatus::Cancelled->label())->toBe('Cancelada')
        ->and(ClassSessionStatus::Recovered->label())->toBe('Recuperada')
        ->and(ClassSessionStatus::Advanced->label())->toBe('Adelantada');
});

// ---------------------------------------------------------------------------
// AttendanceStatus enum
// ---------------------------------------------------------------------------

test('AttendanceStatus has correct values', function () {
    expect(AttendanceStatus::Present->value)->toBe('present')
        ->and(AttendanceStatus::Absent->value)->toBe('absent');
});

test('AttendanceStatus labels are correct', function () {
    expect(AttendanceStatus::Present->label())->toBe('Presente')
        ->and(AttendanceStatus::Absent->label())->toBe('Ausente');
});
