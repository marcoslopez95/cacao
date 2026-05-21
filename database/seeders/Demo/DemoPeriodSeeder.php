<?php

namespace Database\Seeders\Demo;

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Database\Seeder;

class DemoPeriodSeeder extends Seeder
{
    /**
     * Seed academic periods and terms.
     */
    public function run(): void
    {
        $this->seedPeriod2025II();
        $this->seedPeriod2026I();
    }

    /**
     * Seed 2025-II semester with 2 lapses
     */
    private function seedPeriod2025II(): void
    {
        $period = Period::firstOrCreate(
            ['name' => '2025-II'],
            [
                'type' => PeriodType::Semester,
                'start_date' => '2025-07-14',
                'end_date' => '2026-01-16',
                'status' => PeriodStatus::Closed,
            ]
        );

        Lapse::firstOrCreate(
            ['period_id' => $period->id, 'number' => 1],
            [
                'name' => 'Primer Lapso',
                'start_date' => '2025-07-14',
                'end_date' => '2025-10-10',
            ]
        );

        Lapse::firstOrCreate(
            ['period_id' => $period->id, 'number' => 2],
            [
                'name' => 'Segundo Lapso',
                'start_date' => '2025-10-13',
                'end_date' => '2026-01-16',
            ]
        );
    }

    /**
     * Seed 2026-I semester with 2 lapses
     */
    private function seedPeriod2026I(): void
    {
        $period = Period::firstOrCreate(
            ['name' => '2026-I'],
            [
                'type' => PeriodType::Semester,
                'start_date' => '2026-01-19',
                'end_date' => '2026-07-17',
                'status' => PeriodStatus::Active,
            ]
        );

        Lapse::firstOrCreate(
            ['period_id' => $period->id, 'number' => 1],
            [
                'name' => 'Primer Lapso',
                'start_date' => '2026-01-19',
                'end_date' => '2026-04-10',
            ]
        );

        Lapse::firstOrCreate(
            ['period_id' => $period->id, 'number' => 2],
            [
                'name' => 'Segundo Lapso',
                'start_date' => '2026-04-13',
                'end_date' => '2026-07-17',
            ]
        );
    }
}
