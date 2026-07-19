<?php

use App\Models\GradeConfig;
use App\Services\Grade\FinalGradeCalculator;

function calculator(): FinalGradeCalculator
{
    return new FinalGradeCalculator;
}

test('final grade is null when not all non-remedial slots have values', function () {
    $slotData = [
        ['slot_id' => 1, 'weight' => 50, 'value' => 15, 'is_remedial' => false],
        ['slot_id' => 2, 'weight' => 50, 'value' => null, 'is_remedial' => false],
    ];

    expect(calculator()->calculate($slotData, new GradeConfig))->toBeNull();
});

test('final grade is the weighted average of non-remedial slots', function () {
    $slotData = [
        ['slot_id' => 1, 'weight' => 30, 'value' => 10, 'is_remedial' => false],
        ['slot_id' => 2, 'weight' => 30, 'value' => 16, 'is_remedial' => false],
        ['slot_id' => 3, 'weight' => 40, 'value' => 20, 'is_remedial' => false],
    ];

    // 10*0.3 + 16*0.3 + 20*0.4 = 3 + 4.8 + 8 = 15.8
    expect(calculator()->calculate($slotData, new GradeConfig))->toBe('15.8');
});

test('remedial grade elevates final grade when higher than regular average', function () {
    $slotData = [
        ['slot_id' => 1, 'weight' => 100, 'value' => 6, 'is_remedial' => false],
        ['slot_id' => 2, 'weight' => 0, 'value' => 12, 'is_remedial' => true],
    ];

    expect(calculator()->calculate($slotData, new GradeConfig))->toBe('12');
});

test('remedial does not lower final grade when regular average is higher', function () {
    $slotData = [
        ['slot_id' => 1, 'weight' => 100, 'value' => 18, 'is_remedial' => false],
        ['slot_id' => 2, 'weight' => 0, 'value' => 11, 'is_remedial' => true],
    ];

    expect(calculator()->calculate($slotData, new GradeConfig))->toBe('18');
});
