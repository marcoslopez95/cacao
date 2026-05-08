<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Models\Classroom;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    // Valid 45-min start times: 07:00 to 17:15 (end 07:45 to 18:00)
    private const STARTS = [
        '07:00', '07:45', '08:30', '09:15', '10:00', '10:45',
        '11:30', '12:15', '13:00', '13:45', '14:30', '15:15',
        '16:00', '16:45', '17:15',
    ];

    public function definition(): array
    {
        $start = fake()->randomElement(self::STARTS);
        [$h, $m] = explode(':', $start);
        $endMinutes = (int)$h * 60 + (int)$m + 45;
        $end = sprintf('%02d:%02d:00', intdiv($endMinutes, 60), $endMinutes % 60);

        return [
            'section_id'   => Section::factory(),
            'professor_id' => Professor::factory(),
            'classroom_id' => Classroom::factory(),
            'subject_id'   => Subject::factory(),
            'day_of_week'  => fake()->randomElement(DayOfWeek::cases()),
            'start_time'   => $start . ':00',
            'end_time'     => $end,
            'type'         => ScheduleSessionType::Theory,
            'valid_from'   => '2026-01-15',
            'valid_until'  => null,
        ];
    }
}
