<?php

namespace Database\Factories;

use App\Enums\GradeLevel;
use App\Enums\GradeScaleType;
use App\Models\GradeConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeConfig>
 */
class GradeConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'level' => GradeLevel::University,
            'period_id' => null,
            'scale_type' => GradeScaleType::Numeric,
            'scale_min' => '0.00',
            'scale_max' => '20.00',
            'passing_value' => '10.00',
        ];
    }

    public function university(): static
    {
        return $this->state([
            'level' => GradeLevel::University,
            'scale_type' => GradeScaleType::Numeric,
            'scale_min' => '0.00',
            'scale_max' => '20.00',
            'passing_value' => '10.00',
        ]);
    }

    public function primarySecondary(): static
    {
        return $this->state([
            'level' => GradeLevel::PrimarySecondary,
            'scale_type' => GradeScaleType::Numeric,
            'scale_min' => '1.00',
            'scale_max' => '20.00',
            'passing_value' => '10.00',
        ]);
    }

    public function numeric(): static
    {
        return $this->state([
            'scale_type' => GradeScaleType::Numeric,
            'scale_min' => '0.00',
            'scale_max' => '20.00',
        ]);
    }

    public function letter(): static
    {
        return $this->state([
            'level' => GradeLevel::PrimarySecondary,
            'scale_type' => GradeScaleType::Letter,
            'scale_min' => null,
            'scale_max' => null,
            'passing_value' => '60.00',
        ]);
    }
}
