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

        $this->seedSchoolCareer();
        $this->seedSchoolPensum();
        $this->seedSchoolSubjects();
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
            ['career_code' => 'INF', 'code' => 'INF-301', 'name' => 'Estructuras de Datos', 'credits_uc' => 4, 'period_number' => 3],
            ['career_code' => 'INF', 'code' => 'INF-302', 'name' => 'Cálculo III', 'credits_uc' => 4, 'period_number' => 3],
            ['career_code' => 'INF', 'code' => 'INF-303', 'name' => 'Ética Profesional', 'credits_uc' => 2, 'period_number' => 3],
            ['career_code' => 'INF', 'code' => 'INF-401', 'name' => 'Bases de Datos I', 'credits_uc' => 4, 'period_number' => 4],
            ['career_code' => 'INF', 'code' => 'INF-402', 'name' => 'Sistemas Operativos', 'credits_uc' => 4, 'period_number' => 4],
            ['career_code' => 'INF', 'code' => 'INF-403', 'name' => 'Probabilidad y Estadística', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'INF', 'code' => 'INF-501', 'name' => 'Bases de Datos II', 'credits_uc' => 4, 'period_number' => 5],
            ['career_code' => 'INF', 'code' => 'INF-502', 'name' => 'Redes de Computadoras', 'credits_uc' => 3, 'period_number' => 5],
            ['career_code' => 'INF', 'code' => 'INF-503', 'name' => 'Ingeniería de Software I', 'credits_uc' => 4, 'period_number' => 5],
            ['career_code' => 'INF', 'code' => 'INF-601', 'name' => 'Ingeniería de Software II', 'credits_uc' => 4, 'period_number' => 6],
            ['career_code' => 'INF', 'code' => 'INF-602', 'name' => 'Inteligencia Artificial', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'INF', 'code' => 'INF-603', 'name' => 'Sistemas Distribuidos', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'INF', 'code' => 'INF-701', 'name' => 'Seguridad Informática', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'INF', 'code' => 'INF-702', 'name' => 'Desarrollo Web Avanzado', 'credits_uc' => 4, 'period_number' => 7],
            ['career_code' => 'INF', 'code' => 'INF-703', 'name' => 'Gestión de Proyectos TI', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'INF', 'code' => 'INF-801', 'name' => 'Trabajo de Grado I', 'credits_uc' => 4, 'period_number' => 8],
            ['career_code' => 'INF', 'code' => 'INF-802', 'name' => 'Computación en la Nube', 'credits_uc' => 3, 'period_number' => 8],
            ['career_code' => 'INF', 'code' => 'INF-803', 'name' => 'Electiva Profesional', 'credits_uc' => 2, 'period_number' => 8],

            // CIV — Ingeniería Civil
            ['career_code' => 'CIV', 'code' => 'CIV-101', 'name' => 'Cálculo I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-102', 'name' => 'Física I', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-103', 'name' => 'Dibujo Técnico', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-104', 'name' => 'Química General', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'CIV', 'code' => 'CIV-201', 'name' => 'Cálculo II', 'credits_uc' => 4, 'period_number' => 2],
            ['career_code' => 'CIV', 'code' => 'CIV-202', 'name' => 'Física II', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'CIV', 'code' => 'CIV-203', 'name' => 'Mecánica Racional', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'CIV', 'code' => 'CIV-301', 'name' => 'Resistencia de Materiales', 'credits_uc' => 4, 'period_number' => 3],
            ['career_code' => 'CIV', 'code' => 'CIV-302', 'name' => 'Topografía', 'credits_uc' => 3, 'period_number' => 3],
            ['career_code' => 'CIV', 'code' => 'CIV-303', 'name' => 'Estadística Aplicada', 'credits_uc' => 3, 'period_number' => 3],
            ['career_code' => 'CIV', 'code' => 'CIV-401', 'name' => 'Hidráulica', 'credits_uc' => 4, 'period_number' => 4],
            ['career_code' => 'CIV', 'code' => 'CIV-402', 'name' => 'Geotecnia I', 'credits_uc' => 4, 'period_number' => 4],
            ['career_code' => 'CIV', 'code' => 'CIV-403', 'name' => 'Análisis Estructural I', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'CIV', 'code' => 'CIV-501', 'name' => 'Geotecnia II', 'credits_uc' => 4, 'period_number' => 5],
            ['career_code' => 'CIV', 'code' => 'CIV-502', 'name' => 'Análisis Estructural II', 'credits_uc' => 4, 'period_number' => 5],
            ['career_code' => 'CIV', 'code' => 'CIV-503', 'name' => 'Materiales de Construcción', 'credits_uc' => 3, 'period_number' => 5],
            ['career_code' => 'CIV', 'code' => 'CIV-601', 'name' => 'Concreto Armado I', 'credits_uc' => 4, 'period_number' => 6],
            ['career_code' => 'CIV', 'code' => 'CIV-602', 'name' => 'Ingeniería Sanitaria', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'CIV', 'code' => 'CIV-603', 'name' => 'Vías de Comunicación I', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'CIV', 'code' => 'CIV-701', 'name' => 'Concreto Armado II', 'credits_uc' => 4, 'period_number' => 7],
            ['career_code' => 'CIV', 'code' => 'CIV-702', 'name' => 'Vías de Comunicación II', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'CIV', 'code' => 'CIV-703', 'name' => 'Costos y Presupuestos', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'CIV', 'code' => 'CIV-801', 'name' => 'Trabajo de Grado I', 'credits_uc' => 4, 'period_number' => 8],
            ['career_code' => 'CIV', 'code' => 'CIV-802', 'name' => 'Gerencia de Obras', 'credits_uc' => 3, 'period_number' => 8],
            ['career_code' => 'CIV', 'code' => 'CIV-803', 'name' => 'Electiva Profesional', 'credits_uc' => 2, 'period_number' => 8],

            // CON — Contaduría Pública
            ['career_code' => 'CON', 'code' => 'CON-101', 'name' => 'Contabilidad I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-102', 'name' => 'Matemáticas Financieras I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-103', 'name' => 'Economía General', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-104', 'name' => 'Derecho Mercantil', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'CON', 'code' => 'CON-201', 'name' => 'Contabilidad II', 'credits_uc' => 4, 'period_number' => 2],
            ['career_code' => 'CON', 'code' => 'CON-202', 'name' => 'Estadística I', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'CON', 'code' => 'CON-203', 'name' => 'Microeconomía', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'CON', 'code' => 'CON-301', 'name' => 'Contabilidad III', 'credits_uc' => 4, 'period_number' => 3],
            ['career_code' => 'CON', 'code' => 'CON-302', 'name' => 'Matemáticas Financieras II', 'credits_uc' => 3, 'period_number' => 3],
            ['career_code' => 'CON', 'code' => 'CON-303', 'name' => 'Derecho Laboral', 'credits_uc' => 2, 'period_number' => 3],
            ['career_code' => 'CON', 'code' => 'CON-401', 'name' => 'Costos I', 'credits_uc' => 4, 'period_number' => 4],
            ['career_code' => 'CON', 'code' => 'CON-402', 'name' => 'Estadística II', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'CON', 'code' => 'CON-403', 'name' => 'Macroeconomía', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'CON', 'code' => 'CON-501', 'name' => 'Costos II', 'credits_uc' => 4, 'period_number' => 5],
            ['career_code' => 'CON', 'code' => 'CON-502', 'name' => 'Auditoría I', 'credits_uc' => 4, 'period_number' => 5],
            ['career_code' => 'CON', 'code' => 'CON-503', 'name' => 'Derecho Tributario', 'credits_uc' => 3, 'period_number' => 5],
            ['career_code' => 'CON', 'code' => 'CON-601', 'name' => 'Auditoría II', 'credits_uc' => 4, 'period_number' => 6],
            ['career_code' => 'CON', 'code' => 'CON-602', 'name' => 'Contabilidad de Sociedades', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'CON', 'code' => 'CON-603', 'name' => 'Finanzas Públicas', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'CON', 'code' => 'CON-701', 'name' => 'Contabilidad Gubernamental', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'CON', 'code' => 'CON-702', 'name' => 'Auditoría de Sistemas', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'CON', 'code' => 'CON-703', 'name' => 'Ética y Legislación Profesional', 'credits_uc' => 2, 'period_number' => 7],
            ['career_code' => 'CON', 'code' => 'CON-801', 'name' => 'Trabajo de Grado I', 'credits_uc' => 4, 'period_number' => 8],
            ['career_code' => 'CON', 'code' => 'CON-802', 'name' => 'Consultoría Contable', 'credits_uc' => 3, 'period_number' => 8],
            ['career_code' => 'CON', 'code' => 'CON-803', 'name' => 'Electiva Profesional', 'credits_uc' => 2, 'period_number' => 8],

            // ADM — Administración de Empresas
            ['career_code' => 'ADM', 'code' => 'ADM-101', 'name' => 'Fundamentos de Administración', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-102', 'name' => 'Matemáticas para Negocios', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-103', 'name' => 'Economía General', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-104', 'name' => 'Contabilidad I', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'ADM', 'code' => 'ADM-201', 'name' => 'Administración I', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'ADM', 'code' => 'ADM-202', 'name' => 'Finanzas I', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'ADM', 'code' => 'ADM-203', 'name' => 'Microeconomía', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'ADM', 'code' => 'ADM-301', 'name' => 'Administración II', 'credits_uc' => 3, 'period_number' => 3],
            ['career_code' => 'ADM', 'code' => 'ADM-302', 'name' => 'Estadística Aplicada', 'credits_uc' => 3, 'period_number' => 3],
            ['career_code' => 'ADM', 'code' => 'ADM-303', 'name' => 'Derecho Mercantil II', 'credits_uc' => 2, 'period_number' => 3],
            ['career_code' => 'ADM', 'code' => 'ADM-401', 'name' => 'Finanzas II', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'ADM', 'code' => 'ADM-402', 'name' => 'Mercadeo I', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'ADM', 'code' => 'ADM-403', 'name' => 'Macroeconomía', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'ADM', 'code' => 'ADM-501', 'name' => 'Mercadeo II', 'credits_uc' => 3, 'period_number' => 5],
            ['career_code' => 'ADM', 'code' => 'ADM-502', 'name' => 'Gestión de Talento Humano', 'credits_uc' => 3, 'period_number' => 5],
            ['career_code' => 'ADM', 'code' => 'ADM-503', 'name' => 'Investigación de Operaciones', 'credits_uc' => 4, 'period_number' => 5],
            ['career_code' => 'ADM', 'code' => 'ADM-601', 'name' => 'Gerencia Estratégica', 'credits_uc' => 4, 'period_number' => 6],
            ['career_code' => 'ADM', 'code' => 'ADM-602', 'name' => 'Comercio Internacional', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'ADM', 'code' => 'ADM-603', 'name' => 'Gestión de la Calidad', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'ADM', 'code' => 'ADM-701', 'name' => 'Formulación de Proyectos', 'credits_uc' => 4, 'period_number' => 7],
            ['career_code' => 'ADM', 'code' => 'ADM-702', 'name' => 'Gerencia de Producción', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'ADM', 'code' => 'ADM-703', 'name' => 'Ética Empresarial', 'credits_uc' => 2, 'period_number' => 7],
            ['career_code' => 'ADM', 'code' => 'ADM-801', 'name' => 'Trabajo de Grado I', 'credits_uc' => 4, 'period_number' => 8],
            ['career_code' => 'ADM', 'code' => 'ADM-802', 'name' => 'Emprendimiento', 'credits_uc' => 3, 'period_number' => 8],
            ['career_code' => 'ADM', 'code' => 'ADM-803', 'name' => 'Electiva Profesional', 'credits_uc' => 2, 'period_number' => 8],

            // EDU — Educación Mención Matemática
            ['career_code' => 'EDU', 'code' => 'EDU-101', 'name' => 'Cálculo I', 'credits_uc' => 4, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-102', 'name' => 'Álgebra', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-103', 'name' => 'Teoría de la Educación', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-104', 'name' => 'Psicología del Aprendizaje', 'credits_uc' => 3, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-105', 'name' => 'Lenguaje y Comunicación', 'credits_uc' => 2, 'period_number' => 1],
            ['career_code' => 'EDU', 'code' => 'EDU-201', 'name' => 'Cálculo II', 'credits_uc' => 4, 'period_number' => 2],
            ['career_code' => 'EDU', 'code' => 'EDU-202', 'name' => 'Didáctica General', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'EDU', 'code' => 'EDU-203', 'name' => 'Geometría Analítica', 'credits_uc' => 3, 'period_number' => 2],
            ['career_code' => 'EDU', 'code' => 'EDU-301', 'name' => 'Cálculo III', 'credits_uc' => 4, 'period_number' => 3],
            ['career_code' => 'EDU', 'code' => 'EDU-302', 'name' => 'Estadística Educativa', 'credits_uc' => 3, 'period_number' => 3],
            ['career_code' => 'EDU', 'code' => 'EDU-303', 'name' => 'Psicología Evolutiva', 'credits_uc' => 3, 'period_number' => 3],
            ['career_code' => 'EDU', 'code' => 'EDU-401', 'name' => 'Geometría', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'EDU', 'code' => 'EDU-402', 'name' => 'Currículo y Planificación', 'credits_uc' => 3, 'period_number' => 4],
            ['career_code' => 'EDU', 'code' => 'EDU-403', 'name' => 'Tecnología Educativa', 'credits_uc' => 2, 'period_number' => 4],
            ['career_code' => 'EDU', 'code' => 'EDU-501', 'name' => 'Análisis Matemático', 'credits_uc' => 4, 'period_number' => 5],
            ['career_code' => 'EDU', 'code' => 'EDU-502', 'name' => 'Evaluación de los Aprendizajes', 'credits_uc' => 3, 'period_number' => 5],
            ['career_code' => 'EDU', 'code' => 'EDU-503', 'name' => 'Práctica Profesional I', 'credits_uc' => 3, 'period_number' => 5],
            ['career_code' => 'EDU', 'code' => 'EDU-601', 'name' => 'Estructuras Algebraicas', 'credits_uc' => 4, 'period_number' => 6],
            ['career_code' => 'EDU', 'code' => 'EDU-602', 'name' => 'Investigación Educativa', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'EDU', 'code' => 'EDU-603', 'name' => 'Práctica Profesional II', 'credits_uc' => 3, 'period_number' => 6],
            ['career_code' => 'EDU', 'code' => 'EDU-701', 'name' => 'Modelos Matemáticos', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'EDU', 'code' => 'EDU-702', 'name' => 'Orientación Educativa', 'credits_uc' => 2, 'period_number' => 7],
            ['career_code' => 'EDU', 'code' => 'EDU-703', 'name' => 'Práctica Profesional III', 'credits_uc' => 3, 'period_number' => 7],
            ['career_code' => 'EDU', 'code' => 'EDU-801', 'name' => 'Trabajo de Grado I', 'credits_uc' => 4, 'period_number' => 8],
            ['career_code' => 'EDU', 'code' => 'EDU-802', 'name' => 'Historia de la Matemática', 'credits_uc' => 2, 'period_number' => 8],
            ['career_code' => 'EDU', 'code' => 'EDU-803', 'name' => 'Electiva Profesional', 'credits_uc' => 2, 'period_number' => 8],
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

    // -------------------------------------------------------------------------
    // Bachillerato (Educación Media General)
    // -------------------------------------------------------------------------

    private const SCHOOL_CATEGORY = 'Educación Media General';

    private const SCHOOL_CAREER_CODE = 'BACH';

    private const SCHOOL_CAREER_NAME = 'Educación Media General (Bachillerato)';

    private const SCHOOL_PENSUM_NAME = 'Pensum Bachillerato 2020';

    private function seedSchoolCareer(): void
    {
        $category = CareerCategory::firstOrCreate(['name' => self::SCHOOL_CATEGORY]);

        Career::firstOrCreate(
            ['code' => self::SCHOOL_CAREER_CODE],
            [
                'career_category_id' => $category->id,
                'name' => self::SCHOOL_CAREER_NAME,
                'active' => true,
            ],
        );
    }

    private function seedSchoolPensum(): void
    {
        $career = Career::firstOrCreate(['code' => self::SCHOOL_CAREER_CODE]);

        Pensum::firstOrCreate(
            ['career_id' => $career->id, 'name' => self::SCHOOL_PENSUM_NAME],
            [
                'period_type' => 'year',
                'total_periods' => 5,
                'is_active' => true,
            ],
        );
    }

    private function seedSchoolSubjects(): void
    {
        $career = Career::firstOrCreate(['code' => self::SCHOOL_CAREER_CODE]);
        $pensum = Pensum::firstOrCreate(['career_id' => $career->id, 'name' => self::SCHOOL_PENSUM_NAME]);

        foreach ($this->schoolSubjectsData() as $data) {
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
     * @return array<int, array{code: string, name: string, credits_uc: int, period_number: int}>
     */
    private function schoolSubjectsData(): array
    {
        return [
            // Año 1
            ['code' => 'BACH-101', 'name' => 'Castellano y Literatura I', 'credits_uc' => 4, 'period_number' => 1],
            ['code' => 'BACH-102', 'name' => 'Matemática I', 'credits_uc' => 4, 'period_number' => 1],
            ['code' => 'BACH-103', 'name' => 'Ciencias Naturales I', 'credits_uc' => 3, 'period_number' => 1],
            ['code' => 'BACH-104', 'name' => 'Historia Universal', 'credits_uc' => 3, 'period_number' => 1],
            ['code' => 'BACH-105', 'name' => 'Geografía General', 'credits_uc' => 2, 'period_number' => 1],
            ['code' => 'BACH-106', 'name' => 'Inglés I', 'credits_uc' => 2, 'period_number' => 1],
            ['code' => 'BACH-107', 'name' => 'Educación Física I', 'credits_uc' => 1, 'period_number' => 1],

            // Año 2
            ['code' => 'BACH-201', 'name' => 'Castellano y Literatura II', 'credits_uc' => 4, 'period_number' => 2],
            ['code' => 'BACH-202', 'name' => 'Matemática II', 'credits_uc' => 4, 'period_number' => 2],
            ['code' => 'BACH-203', 'name' => 'Biología I', 'credits_uc' => 3, 'period_number' => 2],
            ['code' => 'BACH-204', 'name' => 'Historia de Venezuela I', 'credits_uc' => 3, 'period_number' => 2],
            ['code' => 'BACH-205', 'name' => 'Geografía de Venezuela', 'credits_uc' => 2, 'period_number' => 2],
            ['code' => 'BACH-206', 'name' => 'Inglés II', 'credits_uc' => 2, 'period_number' => 2],
            ['code' => 'BACH-207', 'name' => 'Educación Física II', 'credits_uc' => 1, 'period_number' => 2],

            // Año 3
            ['code' => 'BACH-301', 'name' => 'Castellano y Literatura III', 'credits_uc' => 4, 'period_number' => 3],
            ['code' => 'BACH-302', 'name' => 'Matemática III', 'credits_uc' => 4, 'period_number' => 3],
            ['code' => 'BACH-303', 'name' => 'Física I', 'credits_uc' => 3, 'period_number' => 3],
            ['code' => 'BACH-304', 'name' => 'Química I', 'credits_uc' => 3, 'period_number' => 3],
            ['code' => 'BACH-305', 'name' => 'Historia de Venezuela II', 'credits_uc' => 3, 'period_number' => 3],
            ['code' => 'BACH-306', 'name' => 'Inglés III', 'credits_uc' => 2, 'period_number' => 3],
            ['code' => 'BACH-307', 'name' => 'Formación Ciudadana', 'credits_uc' => 2, 'period_number' => 3],

            // Año 4
            ['code' => 'BACH-401', 'name' => 'Castellano y Literatura IV', 'credits_uc' => 4, 'period_number' => 4],
            ['code' => 'BACH-402', 'name' => 'Matemática IV', 'credits_uc' => 4, 'period_number' => 4],
            ['code' => 'BACH-403', 'name' => 'Física II', 'credits_uc' => 3, 'period_number' => 4],
            ['code' => 'BACH-404', 'name' => 'Química II', 'credits_uc' => 3, 'period_number' => 4],
            ['code' => 'BACH-405', 'name' => 'Biología II', 'credits_uc' => 3, 'period_number' => 4],
            ['code' => 'BACH-406', 'name' => 'Inglés IV', 'credits_uc' => 2, 'period_number' => 4],
            ['code' => 'BACH-407', 'name' => 'Educación para el Trabajo', 'credits_uc' => 2, 'period_number' => 4],

            // Año 5
            ['code' => 'BACH-501', 'name' => 'Castellano y Literatura V', 'credits_uc' => 4, 'period_number' => 5],
            ['code' => 'BACH-502', 'name' => 'Matemática V', 'credits_uc' => 4, 'period_number' => 5],
            ['code' => 'BACH-503', 'name' => 'Física III', 'credits_uc' => 3, 'period_number' => 5],
            ['code' => 'BACH-504', 'name' => 'Química III', 'credits_uc' => 3, 'period_number' => 5],
            ['code' => 'BACH-505', 'name' => 'Historia Contemporánea', 'credits_uc' => 3, 'period_number' => 5],
            ['code' => 'BACH-506', 'name' => 'Inglés V', 'credits_uc' => 2, 'period_number' => 5],
            ['code' => 'BACH-507', 'name' => 'Orientación Vocacional', 'credits_uc' => 1, 'period_number' => 5],
        ];
    }
}
