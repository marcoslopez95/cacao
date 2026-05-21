<?php

namespace Database\Seeders\Demo;

use App\Enums\ClassroomType;
use App\Models\Building;
use App\Models\Classroom;
use Illuminate\Database\Seeder;

class DemoInfrastructureSeeder extends Seeder
{
    /**
     * Seed infrastructure data: buildings and classrooms.
     */
    public function run(): void
    {
        // Create buildings
        $buildingA = Building::firstOrCreate(['name' => 'Edificio A — Ingeniería']);
        $buildingB = Building::firstOrCreate(['name' => 'Edificio B — Ciencias y Humanidades']);

        // Edificio A classrooms
        $this->createClassroom($buildingA, 'A-101', ClassroomType::Theory, 40);
        $this->createClassroom($buildingA, 'A-102', ClassroomType::Theory, 35);
        $this->createClassroom($buildingA, 'A-103', ClassroomType::Theory, 30);
        $this->createClassroom($buildingA, 'A-201', ClassroomType::Theory, 35);
        $this->createClassroom($buildingA, 'A-202', ClassroomType::Theory, 30);
        $this->createClassroom($buildingA, 'A-Lab1', ClassroomType::Laboratory, 25);
        $this->createClassroom($buildingA, 'A-Lab2', ClassroomType::Laboratory, 20);

        // Edificio B classrooms
        $this->createClassroom($buildingB, 'B-101', ClassroomType::Theory, 40);
        $this->createClassroom($buildingB, 'B-102', ClassroomType::Theory, 35);
        $this->createClassroom($buildingB, 'B-103', ClassroomType::Theory, 30);
        $this->createClassroom($buildingB, 'B-104', ClassroomType::Theory, 25);
        $this->createClassroom($buildingB, 'B-201', ClassroomType::Theory, 35);
        $this->createClassroom($buildingB, 'B-202', ClassroomType::Theory, 30);
        $this->createClassroom($buildingB, 'B-Lab1', ClassroomType::Laboratory, 20);
        $this->createClassroom($buildingB, 'B-Lab2', ClassroomType::Laboratory, 20);
    }

    /**
     * Create a classroom with idempotency check.
     */
    private function createClassroom(Building $building, string $identifier, ClassroomType $type, int $capacity): void
    {
        Classroom::firstOrCreate(
            ['identifier' => $identifier],
            [
                'building_id' => $building->id,
                'type' => $type,
                'capacity' => $capacity,
            ]
        );
    }
}
