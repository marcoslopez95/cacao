<?php

namespace Database\Seeders\Catalogs;

use App\Models\Catalogs\AcademicShift;
use App\Models\Catalogs\AcademicStatus;
use App\Models\Catalogs\AdmissionType;
use App\Models\Catalogs\EducationLevel;
use App\Models\Catalogs\SchoolGrade;
use App\Models\Catalogs\StudyModality;
use Illuminate\Database\Seeder;

class AcademicCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedEducationLevels();
        $this->seedAcademicStatuses();
        $this->seedStudyModalities();
        $this->seedAcademicShifts();
        $this->seedAdmissionTypes();
        $this->seedSchoolGrades();
    }

    private function seedEducationLevels(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $levels
         */
        $levels = [
            ['code' => 'preschool', 'name' => 'Preescolar', 'sort_order' => 1],
            ['code' => 'primary', 'name' => 'Primaria', 'sort_order' => 2],
            ['code' => 'secondary', 'name' => 'Secundaria', 'sort_order' => 3],
            ['code' => 'technical_tsu', 'name' => 'Técnico Superior Universitario', 'sort_order' => 4],
            ['code' => 'undergraduate', 'name' => 'Pregrado', 'sort_order' => 5],
            ['code' => 'postgraduate', 'name' => 'Posgrado', 'sort_order' => 6],
        ];

        foreach ($levels as $level) {
            EducationLevel::firstOrCreate(
                ['code' => $level['code']],
                ['name' => $level['name'], 'active' => true, 'sort_order' => $level['sort_order']],
            );
        }
    }

    private function seedAcademicStatuses(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $statuses
         */
        $statuses = [
            ['code' => 'active', 'name' => 'Activo', 'sort_order' => 1],
            ['code' => 'withdrawn', 'name' => 'Retirado', 'sort_order' => 2],
            ['code' => 'graduated', 'name' => 'Egresado', 'sort_order' => 3],
            ['code' => 'graduated_no_title', 'name' => 'Egresado sin título', 'sort_order' => 4],
            ['code' => 'suspended', 'name' => 'Suspendido', 'sort_order' => 5],
            ['code' => 'exchange', 'name' => 'Intercambio', 'sort_order' => 6],
        ];

        foreach ($statuses as $status) {
            AcademicStatus::firstOrCreate(
                ['code' => $status['code']],
                ['name' => $status['name'], 'active' => true, 'sort_order' => $status['sort_order']],
            );
        }
    }

    private function seedStudyModalities(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $modalities
         */
        $modalities = [
            ['code' => 'in_person', 'name' => 'Presencial', 'sort_order' => 1],
            ['code' => 'online', 'name' => 'En línea', 'sort_order' => 2],
            ['code' => 'hybrid', 'name' => 'Híbrida', 'sort_order' => 3],
        ];

        foreach ($modalities as $modality) {
            StudyModality::firstOrCreate(
                ['code' => $modality['code']],
                ['name' => $modality['name'], 'active' => true, 'sort_order' => $modality['sort_order']],
            );
        }
    }

    private function seedAcademicShifts(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $shifts
         */
        $shifts = [
            ['code' => 'morning', 'name' => 'Matutino', 'sort_order' => 1],
            ['code' => 'afternoon', 'name' => 'Vespertino', 'sort_order' => 2],
            ['code' => 'night', 'name' => 'Nocturno', 'sort_order' => 3],
        ];

        foreach ($shifts as $shift) {
            AcademicShift::firstOrCreate(
                ['code' => $shift['code']],
                ['name' => $shift['name'], 'active' => true, 'sort_order' => $shift['sort_order']],
            );
        }
    }

    private function seedAdmissionTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'regular', 'name' => 'Regular', 'sort_order' => 1],
            ['code' => 'transfer', 'name' => 'Traslado', 'sort_order' => 2],
            ['code' => 'equivalence', 'name' => 'Equivalencia', 'sort_order' => 3],
        ];

        foreach ($types as $type) {
            AdmissionType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedSchoolGrades(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $grades
         */
        $grades = [
            ['code' => 'primary_1', 'name' => '1er grado', 'sort_order' => 1],
            ['code' => 'primary_2', 'name' => '2do grado', 'sort_order' => 2],
            ['code' => 'primary_3', 'name' => '3er grado', 'sort_order' => 3],
            ['code' => 'primary_4', 'name' => '4to grado', 'sort_order' => 4],
            ['code' => 'primary_5', 'name' => '5to grado', 'sort_order' => 5],
            ['code' => 'primary_6', 'name' => '6to grado', 'sort_order' => 6],
            ['code' => 'secondary_1', 'name' => '1er año', 'sort_order' => 7],
            ['code' => 'secondary_2', 'name' => '2do año', 'sort_order' => 8],
            ['code' => 'secondary_3', 'name' => '3er año', 'sort_order' => 9],
            ['code' => 'secondary_4', 'name' => '4to año', 'sort_order' => 10],
            ['code' => 'secondary_5', 'name' => '5to año', 'sort_order' => 11],
        ];

        foreach ($grades as $grade) {
            SchoolGrade::firstOrCreate(
                ['code' => $grade['code']],
                ['name' => $grade['name'], 'active' => true, 'sort_order' => $grade['sort_order']],
            );
        }
    }
}
