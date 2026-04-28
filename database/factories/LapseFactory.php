<?php

namespace Database\Factories;

use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lapse> */
class LapseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'period_id'  => Period::factory()->year()->state([
                'start_date' => '2025-01-01',
                'end_date'   => '2025-12-01',
            ]),
            'number'     => 1,
            'name'       => 'Primer Lapso',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-03-31',
        ];
    }

    public function forPeriod(Period $period, int $number = 1): static
    {
        $start = $period->start_date->copy()->addDays(($number - 1) * 60);
        $end   = $start->copy()->addDays(58);

        if ($end->gt($period->end_date)) {
            $end = $period->end_date->copy()->subDay();
        }

        return $this->state([
            'period_id'  => $period->id,
            'number'     => $number,
            'name'       => match ($number) {
                1        => 'Primer Lapso',
                2        => 'Segundo Lapso',
                3        => 'Tercer Lapso',
                default  => "Lapso {$number}",
            },
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
        ]);
    }
}
