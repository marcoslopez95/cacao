<?php

namespace Database\Factories;

use App\Models\GradeConfig;
use App\Models\GradeLetterValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeLetterValue>
 */
class GradeLetterValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'grade_config_id' => GradeConfig::factory()->letter(),
            'letter' => 'A',
            'numeric_equiv' => '100.00',
            'is_passing' => true,
            'sort_order' => 1,
        ];
    }
}
