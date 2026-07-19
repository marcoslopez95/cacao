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
     *
     * University semesters (chronological): 2022-II, 2023-I, 2023-II, 2024-I,
     * 2024-II, 2025-I, 2025-II, 2026-I (active/current).
     * School years (chronological): 2021-2022 … 2025-2026 (active/current).
     */
    public function run(): void
    {
        $this->seedUniversitySemesters();
        $this->seedSchoolYears();
    }

    // -------------------------------------------------------------------------
    // University semesters
    // -------------------------------------------------------------------------

    private function seedUniversitySemesters(): void
    {
        $this->seedSemester(
            '2022-II', '2022-07-18', '2023-01-20', PeriodStatus::Closed,
            [
                ['Primer Lapso', '2022-07-18', '2022-10-14'],
                ['Segundo Lapso', '2022-10-17', '2023-01-20'],
            ],
        );

        $this->seedSemester(
            '2023-I', '2023-01-23', '2023-07-21', PeriodStatus::Closed,
            [
                ['Primer Lapso', '2023-01-23', '2023-04-14'],
                ['Segundo Lapso', '2023-04-17', '2023-07-21'],
            ],
        );

        $this->seedSemester(
            '2023-II', '2023-07-17', '2024-01-19', PeriodStatus::Closed,
            [
                ['Primer Lapso', '2023-07-17', '2023-10-13'],
                ['Segundo Lapso', '2023-10-16', '2024-01-19'],
            ],
        );

        $this->seedSemester(
            '2024-I', '2024-01-22', '2024-07-19', PeriodStatus::Closed,
            [
                ['Primer Lapso', '2024-01-22', '2024-04-12'],
                ['Segundo Lapso', '2024-04-15', '2024-07-19'],
            ],
        );

        $this->seedSemester(
            '2024-II', '2024-07-15', '2025-01-17', PeriodStatus::Closed,
            [
                ['Primer Lapso', '2024-07-15', '2024-10-11'],
                ['Segundo Lapso', '2024-10-14', '2025-01-17'],
            ],
        );

        $this->seedSemester(
            '2025-I', '2025-01-20', '2025-07-18', PeriodStatus::Closed,
            [
                ['Primer Lapso', '2025-01-20', '2025-04-11'],
                ['Segundo Lapso', '2025-04-14', '2025-07-18'],
            ],
        );

        $this->seedSemester(
            '2025-II', '2025-07-14', '2026-01-16', PeriodStatus::Closed,
            [
                ['Primer Lapso', '2025-07-14', '2025-10-10'],
                ['Segundo Lapso', '2025-10-13', '2026-01-16'],
            ],
        );

        $this->seedSemester(
            '2026-I', '2026-01-19', '2026-07-17', PeriodStatus::Active,
            [
                ['Primer Lapso', '2026-01-19', '2026-04-10'],
                ['Segundo Lapso', '2026-04-13', '2026-07-17'],
            ],
        );
    }

    // -------------------------------------------------------------------------
    // School years
    // -------------------------------------------------------------------------

    private function seedSchoolYears(): void
    {
        $this->seedYear(
            '2021-2022', '2021-09-20', '2022-07-20', PeriodStatus::Closed,
            [
                ['Primer Momento', '2021-09-20', '2021-12-20'],
                ['Segundo Momento', '2021-12-21', '2022-03-20'],
                ['Tercer Momento', '2022-03-21', '2022-07-20'],
            ],
        );

        $this->seedYear(
            '2022-2023', '2022-09-19', '2023-07-19', PeriodStatus::Closed,
            [
                ['Primer Momento', '2022-09-19', '2022-12-19'],
                ['Segundo Momento', '2022-12-20', '2023-03-19'],
                ['Tercer Momento', '2023-03-20', '2023-07-19'],
            ],
        );

        $this->seedYear(
            '2023-2024', '2023-09-18', '2024-07-18', PeriodStatus::Closed,
            [
                ['Primer Momento', '2023-09-18', '2023-12-18'],
                ['Segundo Momento', '2023-12-19', '2024-03-18'],
                ['Tercer Momento', '2024-03-19', '2024-07-18'],
            ],
        );

        $this->seedYear(
            '2024-2025', '2024-09-16', '2025-07-16', PeriodStatus::Closed,
            [
                ['Primer Momento', '2024-09-16', '2024-12-16'],
                ['Segundo Momento', '2024-12-17', '2025-03-16'],
                ['Tercer Momento', '2025-03-17', '2025-07-16'],
            ],
        );

        $this->seedYear(
            '2025-2026', '2025-09-15', '2026-07-15', PeriodStatus::Active,
            [
                ['Primer Momento', '2025-09-15', '2025-12-15'],
                ['Segundo Momento', '2025-12-16', '2026-03-15'],
                ['Tercer Momento', '2026-03-16', '2026-07-15'],
            ],
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $lapses  [name, start, end]
     */
    private function seedSemester(string $name, string $start, string $end, PeriodStatus $status, array $lapses): void
    {
        $period = Period::firstOrCreate(
            ['name' => $name],
            [
                'type' => PeriodType::Semester,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $status,
            ]
        );

        $this->seedLapses($period, $lapses);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $lapses  [name, start, end]
     */
    private function seedYear(string $name, string $start, string $end, PeriodStatus $status, array $lapses): void
    {
        $period = Period::firstOrCreate(
            ['name' => $name],
            [
                'type' => PeriodType::Year,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $status,
            ]
        );

        $this->seedLapses($period, $lapses);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $lapses  [name, start, end]
     */
    private function seedLapses(Period $period, array $lapses): void
    {
        foreach ($lapses as $index => [$lapseName, $lapseStart, $lapseEnd]) {
            Lapse::firstOrCreate(
                ['period_id' => $period->id, 'number' => $index + 1],
                [
                    'name' => $lapseName,
                    'start_date' => $lapseStart,
                    'end_date' => $lapseEnd,
                ]
            );
        }
    }
}
