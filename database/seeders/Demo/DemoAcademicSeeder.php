<?php

namespace Database\Seeders\Demo;

use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\Pensum;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DemoAcademicSeeder extends Seeder
{
    /**
     * Seed academic data: career categories, careers, curriculums, and subjects.
     */
    public function run(): void
    {
        $this->seedCategories();
        $this->seedCareers();
        $this->seedPensums();
        $this->seedSubjects();
        $this->seedPrerequisites();
    }

    // -------------------------------------------------------------------------
    // Categories
    // -------------------------------------------------------------------------

    private function seedCategories(): void
    {
        foreach ($this->categoriesData() as $name) {
            CareerCategory::firstOrCreate(['name' => $name]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function categoriesData(): array
    {
        return [
            'Ingeniería',
            'Ciencias Económicas',
            'Humanidades y Educación',
        ];
    }

    // -------------------------------------------------------------------------
    // Careers
    // -------------------------------------------------------------------------

    private function seedCareers(): void
    {
        foreach ($this->careersData() as $data) {
            $category = CareerCategory::firstOrCreate(['name' => $data['category']]);

            Career::firstOrCreate(
                ['code' => $data['code']],
                [
                    'career_category_id' => $category->id,
                    'name' => $data['name'],
                    'active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array{code: string, name: string, category: string}>
     */
    private function careersData(): array
    {
        return [
            ['code' => 'INF', 'name' => 'Ingeniería en Informática', 'category' => 'Ingeniería'],
            ['code' => 'CIV', 'name' => 'Ingeniería Civil', 'category' => 'Ingeniería'],
            ['code' => 'CON', 'name' => 'Contaduría Pública', 'category' => 'Ciencias Económicas'],
            ['code' => 'ADM', 'name' => 'Administración de Empresas', 'category' => 'Ciencias Económicas'],
            ['code' => 'EDU', 'name' => 'Educación Mención Matemática', 'category' => 'Humanidades y Educación'],
        ];
    }

    // -------------------------------------------------------------------------
    // Pensums
    // -------------------------------------------------------------------------

    private function seedPensums(): void
    {
        foreach ($this->careersData() as $careerData) {
            $career = Career::firstOrCreate(
                ['code' => $careerData['code']],
            );

            Pensum::firstOrCreate(
                ['career_id' => $career->id, 'name' => 'Pensum 2020'],
                [
                    'period_type' => 'semester',
                    'total_periods' => 8,
                    'is_active' => true,
                ],
            );
        }
    }

    // -------------------------------------------------------------------------
    // Subjects
    // -------------------------------------------------------------------------

    private function seedSubjects(): void
    {
        foreach ($this->subjectsData() as $data) {
            $career = Career::firstOrCreate(['code' => $data['career_code']]);
            $pensum = Pensum::firstOrCreate(
                ['career_id' => $career->id, 'name' => 'Pensum 2020'],
            );

            Subject::firstOrCreate(
                ['code' => $data['code']],
                [
                    'pensum_id' => $pensum->id,
                    'name' => $data['name'],
                    'credits_uc' => $data['credits_uc'],
                    'period_number' => $data['period_number'],
                    'description' => null,
                ],
            );
        }
    }

    /**
     * @return array<int, array{career_code: string, code: string, name: string, credits_uc: int, period_number: int}>
     */
    private function subjectsData(): array
    {
        return [
            // INF — Ingeniería en Informática
            ['career_code' => 'INF', 'code' => 'INF-101', 'name' => 'Cálculo I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'INF', 'code' => 'INF-102', 'name' => 'Programación I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'INF', 'code' => 'INF-103', 'name' => 'Física I', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'INF', 'code' => 'INF-104', 'name' => 'Álgebra Lineal', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'INF', 'code' => 'INF-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'INF', 'code' => 'INF-201', 'name' => 'Cálculo II', 'credits_uc' => 4, 'period_number' => 2],
            ['career_code' => 'INF', 'code' => 'INF-202', 'name' => 'Programación II', 'credits_uc' => 4, 'period_number' => 2],
            ['career_code' => 'INF', 'code' => 'INF-203', 'name' => 'Física II', 'credits_uc' => 3, 'period_number' => 2],

            // CIV — Ingeniería Civil
            ['career_code' => 'CIV', 'code' => 'CIV-101', 'name' => 'Cálculo I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-102', 'name' => 'Física I', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-103', 'name' => 'Dibujo Técnico', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-104', 'name' => 'Química General', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-201', 'name' => 'Cálculo II', 'credits_uc' => 4, 'period_number' => 2],
            ['career_code' => 'CIV', 'code' => 'CIV-202', 'name' => 'Física II', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'CIV', 'code' => 'CIV-203', 'name' => 'Mecánica Racional', 'credits_uc' => 3, 'period_number' => 2],

            // CON — Contaduría Pública
            ['career_code' => 'CON', 'code' => 'CON-101', 'name' => 'Contabilidad I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-102', 'name' => 'Matemáticas Financieras I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-103', 'name' => 'Economía General', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-104', 'name' => 'Derecho Mercantil', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-201', 'name' => 'Contabilidad II', 'credits_uc' => 4, 'period_number' => 2],
            ['career_code' => 'CON', 'code' => 'CON-202', 'name' => 'Estadística I', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'CON', 'code' => 'CON-203', 'name' => 'Microeconomía', 'credits_uc' => 3, 'period_number' => 2],

            // ADM — Administración de Empresas
            ['career_code' => 'ADM', 'code' => 'ADM-101', 'name' => 'Fundamentos de Administración', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-102', 'name' => 'Matemáticas para Negocios', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-103', 'name' => 'Economía General', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-104', 'name' => 'Contabilidad I', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-201', 'name' => 'Administración I', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'ADM', 'code' => 'ADM-202', 'name' => 'Finanzas I', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'ADM', 'code' => 'ADM-203', 'name' => 'Microeconomía', 'credits_uc' => 3, 'period_number' => 2],

            // EDU — Educación Mención Matemática
            ['career_code' => 'EDU', 'code' => 'EDU-101', 'name' => 'Cálculo I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-102', 'name' => 'Álgebra', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-103', 'name' => 'Teoría de la Educación', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-104', 'name' => 'Psicología del Aprendizaje', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-201', 'name' => 'Cálculo II', 'credits_uc' => 4, 'period_number' => 2],
            ['career_code' => 'EDU', 'code' => 'EDU-202', 'name' => 'Didáctica General', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'EDU', 'code' => 'EDU-203', 'name' => 'Geometría Analítica', 'credits_uc' => 3, 'period_number' => 2],
        ];
    }

    // -------------------------------------------------------------------------
    // Prerequisites
    // -------------------------------------------------------------------------

    private function seedPrerequisites(): void
    {
        foreach ($this->prerequisitesData() as [$subjectCode, $prereqCode]) {
            $subject = Subject::firstOrCreate(['code' => $subjectCode]);
            $prereq = Subject::firstOrCreate(['code' => $prereqCode]);

            $alreadyExists = $subject->prerequisites()
                ->where('prerequisite_id', $prereq->id)
                ->exists();

            if (! $alreadyExists) {
                $subject->prerequisites()->attach($prereq->id);
            }
        }
    }

    /**
     * Each entry: [subject_code, prerequisite_code]
     * Meaning: prerequisite_code is required before enrolling in subject_code.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function prerequisitesData(): array
    {
        return [
            // INF
            ['INF-201', 'INF-101'], // Cálculo II → Cálculo I
            ['INF-202', 'INF-102'], // Programación II → Programación I
            ['INF-203', 'INF-103'], // Física II → Física I

            // CIV
            ['CIV-201', 'CIV-101'], // Cálculo II → Cálculo I
            ['CIV-202', 'CIV-102'], // Física II → Física I
            ['CIV-203', 'CIV-102'], // Mecánica Racional → Física I

            // CON
            ['CON-201', 'CON-101'], // Contabilidad II → Contabilidad I
            ['CON-202', 'CON-102'], // Estadística I → Matemáticas Financieras I
            ['CON-203', 'CON-103'], // Microeconomía → Economía General

            // ADM
            ['ADM-201', 'ADM-101'], // Administración I → Fundamentos de Administración
            ['ADM-202', 'ADM-102'], // Finanzas I → Matemáticas para Negocios
            ['ADM-203', 'ADM-103'], // Microeconomía → Economía General

            // EDU
            ['EDU-201', 'EDU-101'], // Cálculo II → Cálculo I
            ['EDU-202', 'EDU-103'], // Didáctica General → Teoría de la Educación
            ['EDU-203', 'EDU-102'], // Geometría Analítica → Álgebra
        ];
    }
}
