# Demo Seeder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear un `DemoSeeder` modular que siembre datos académicos venezolanos realistas — dos instituciones (universidad + secundaria), 5 carreras, 40 materias, 12 profesores, 140 estudiantes e inscripciones con estados variados — idempotente y extensible.

**Architecture:** Orquestador `DemoSeeder` que llama a sub-seeders en `database/seeders/Demo/` en orden de dependencia FK. Cada sub-seeder usa `firstOrCreate`/`updateOrCreate` sobre un campo único natural para garantizar idempotencia. `DemoSeeder` invoca `DatabaseSeeder` como primer paso.

**Tech Stack:** PHP 8.3 · Laravel 13 · Eloquent factories · Spatie Permission · Pest v4

---

## File Map

| Acción | Archivo |
|--------|---------|
| Create | `database/seeders/DemoSeeder.php` |
| Create | `database/seeders/Demo/DemoAcademicSeeder.php` |
| Create | `database/seeders/Demo/DemoInfrastructureSeeder.php` |
| Create | `database/seeders/Demo/DemoPeriodSeeder.php` |
| Create | `database/seeders/Demo/DemoProfessorsSeeder.php` |
| Create | `database/seeders/Demo/DemoStudentsSeeder.php` |
| Create | `database/seeders/Demo/DemoSectionsSeeder.php` |
| Create | `database/seeders/Demo/DemoEnrollmentSeeder.php` |
| Create | `tests/Feature/DemoSeederTest.php` |
| Modify | `feature_list.json` |

---

## Task 1: Feature registration + DemoSeeder orchestrator + sub-seeder stubs

**Files:**
- Modify: `feature_list.json`
- Create: `database/seeders/DemoSeeder.php`
- Create: `database/seeders/Demo/DemoAcademicSeeder.php` (stub)
- Create: `database/seeders/Demo/DemoInfrastructureSeeder.php` (stub)
- Create: `database/seeders/Demo/DemoPeriodSeeder.php` (stub)
- Create: `database/seeders/Demo/DemoProfessorsSeeder.php` (stub)
- Create: `database/seeders/Demo/DemoStudentsSeeder.php` (stub)
- Create: `database/seeders/Demo/DemoSectionsSeeder.php` (stub)
- Create: `database/seeders/Demo/DemoEnrollmentSeeder.php` (stub)

- [ ] **Step 1: Registrar feature en feature_list.json**

Reemplazar el contenido de `feature_list.json` para agregar `03-demo-seeder`:

```json
[
  {
    "id": "01-enrollment-backend",
    "name": "Enrollment Backend — quota control, prerequisite validation, student/guardian support",
    "status": "completed",
    "current_task": 10,
    "plan": "docs/superpowers/plans/2026-05-20-enrollment-backend.md"
  },
  {
    "id": "02-enrollment-frontend",
    "name": "Enrollment Frontend Integration — conectar Vue con backend real, eliminar mock data",
    "status": "completed",
    "current_task": 9,
    "plan": "specs/enrollment-frontend/tasks.md"
  },
  {
    "id": "03-demo-seeder",
    "name": "Demo Seeder — datos realistas venezolanos para demo y desarrollo",
    "status": "in_progress",
    "current_task": 1,
    "plan": "docs/superpowers/plans/2026-05-21-demo-seeder.md"
  }
]
```

- [ ] **Step 2: Crear DemoSeeder.php (orquestador)**

```php
<?php

namespace Database\Seeders;

use Database\Seeders\Demo\DemoAcademicSeeder;
use Database\Seeders\Demo\DemoEnrollmentSeeder;
use Database\Seeders\Demo\DemoInfrastructureSeeder;
use Database\Seeders\Demo\DemoPeriodSeeder;
use Database\Seeders\Demo\DemoProfessorsSeeder;
use Database\Seeders\Demo\DemoSectionsSeeder;
use Database\Seeders\Demo\DemoStudentsSeeder;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DatabaseSeeder::class,
            DemoAcademicSeeder::class,
            DemoInfrastructureSeeder::class,
            DemoPeriodSeeder::class,
            DemoProfessorsSeeder::class,
            DemoStudentsSeeder::class,
            DemoSectionsSeeder::class,
            DemoEnrollmentSeeder::class,
        ]);
    }
}
```

- [ ] **Step 3: Crear stubs de sub-seeders en `database/seeders/Demo/`**

Crear los 7 archivos siguientes. Todos usan el mismo patrón de stub:

`DemoAcademicSeeder.php`:
```php
<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

class DemoAcademicSeeder extends Seeder
{
    public function run(): void {}
}
```

Repetir para: `DemoInfrastructureSeeder`, `DemoPeriodSeeder`, `DemoProfessorsSeeder`, `DemoStudentsSeeder`, `DemoSectionsSeeder`, `DemoEnrollmentSeeder` — mismo namespace `Database\Seeders\Demo`, mismo nombre de clase.

- [ ] **Step 4: Verificar que el orquestador corre sin errores**

```bash
vendor/bin/sail artisan db:seed --class=DemoSeeder
```

Resultado esperado: `Seeding: DatabaseSeeder` y sub-seeders sin errores (no crea data aún).

- [ ] **Step 5: Commit**

```bash
git add feature_list.json database/seeders/DemoSeeder.php database/seeders/Demo/
git commit -m "feat: add DemoSeeder orchestrator and sub-seeder stubs"
```

---

## Task 2: DemoAcademicSeeder — categorías, carreras, pensums, materias, prelaciones

**Files:**
- Create: `tests/Feature/DemoSeederTest.php` (primera versión — solo assertions académicas)
- Modify: `database/seeders/Demo/DemoAcademicSeeder.php`

**Data plan:**
- 3 categorías, 5 carreras, 5 pensums, 40 materias (8 por pensum), ~15 prelaciones
- Pensums: `period_type = 'semester'`, `total_periods = 8`
- Materias: semestres 1 (5 materias) y 2 (3 materias) por pensum

- [ ] **Step 1: Escribir test fallido**

`tests/Feature/DemoSeederTest.php`:
```php
<?php

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds academic structure', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\CareerCategory::count())->toBeGreaterThanOrEqual(3);
    expect(\App\Models\Career::count())->toBeGreaterThanOrEqual(5);
    expect(\App\Models\Pensum::count())->toBeGreaterThanOrEqual(5);
    expect(\App\Models\Subject::count())->toBeGreaterThanOrEqual(40);
});
```

- [ ] **Step 2: Correr test para verificar que falla**

```bash
vendor/bin/sail artisan test --compact --filter="seeds academic structure"
```

Resultado esperado: FAIL — counts son 0.

- [ ] **Step 3: Implementar DemoAcademicSeeder**

```php
<?php

namespace Database\Seeders\Demo;

use App\Models\Career;
use App\Models\CareerCategory;
use App\Models\Pensum;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DemoAcademicSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAcademicStructure();
    }

    private function seedAcademicStructure(): void
    {
        $ingenieria   = CareerCategory::firstOrCreate(['name' => 'Ingeniería']);
        $economicas   = CareerCategory::firstOrCreate(['name' => 'Ciencias Económicas']);
        $humanidades  = CareerCategory::firstOrCreate(['name' => 'Humanidades y Educación']);

        $this->seedCareer($ingenieria->id, 'Ingeniería en Informática', 'INF', $this->subjectsINF());
        $this->seedCareer($ingenieria->id, 'Ingeniería Civil', 'CIV', $this->subjectsCIV());
        $this->seedCareer($economicas->id, 'Contaduría Pública', 'CON', $this->subjectsCON());
        $this->seedCareer($economicas->id, 'Administración de Empresas', 'ADM', $this->subjectsADM());
        $this->seedCareer($humanidades->id, 'Educación Mención Matemática', 'EDU', $this->subjectsEDU());
    }

    /**
     * @param array<int, array{code: string, name: string, uc: int, sem: int, prereq?: string}> $subjectsData
     */
    private function seedCareer(int $categoryId, string $careerName, string $careerCode, array $subjectsData): void
    {
        $career = Career::firstOrCreate(
            ['code' => $careerCode],
            ['career_category_id' => $categoryId, 'name' => $careerName, 'active' => true],
        );

        $pensum = Pensum::firstOrCreate(
            ['career_id' => $career->id, 'name' => "Plan de Estudios 2020"],
            ['period_type' => 'semester', 'total_periods' => 8, 'is_active' => true],
        );

        $created = [];

        foreach ($subjectsData as $data) {
            $subject = Subject::firstOrCreate(
                ['code' => $data['code']],
                [
                    'pensum_id'     => $pensum->id,
                    'name'          => $data['name'],
                    'credits_uc'    => $data['uc'],
                    'period_number' => $data['sem'],
                    'description'   => null,
                ],
            );
            $created[$data['code']] = $subject;
        }

        // Attach prerequisites after all subjects exist
        foreach ($subjectsData as $data) {
            if (isset($data['prereq'])) {
                $subject  = $created[$data['code']];
                $prereq   = $created[$data['prereq']];
                if (! $subject->prerequisites()->where('prerequisite_id', $prereq->id)->exists()) {
                    $subject->prerequisites()->attach($prereq->id);
                }
            }
        }
    }

    /** @return array<int, array{code: string, name: string, uc: int, sem: int, prereq?: string}> */
    private function subjectsINF(): array
    {
        return [
            ['code' => 'INF-101', 'name' => 'Cálculo I',                 'uc' => 4, 'sem' => 1],
            ['code' => 'INF-102', 'name' => 'Programación I',            'uc' => 4, 'sem' => 1],
            ['code' => 'INF-103', 'name' => 'Física I',                  'uc' => 3, 'sem' => 1],
            ['code' => 'INF-104', 'name' => 'Álgebra Lineal',            'uc' => 3, 'sem' => 1],
            ['code' => 'INF-105', 'name' => 'Lenguaje y Comunicación',   'uc' => 2, 'sem' => 1],
            ['code' => 'INF-201', 'name' => 'Cálculo II',                'uc' => 4, 'sem' => 2, 'prereq' => 'INF-101'],
            ['code' => 'INF-202', 'name' => 'Programación II',           'uc' => 4, 'sem' => 2, 'prereq' => 'INF-102'],
            ['code' => 'INF-203', 'name' => 'Física II',                 'uc' => 3, 'sem' => 2, 'prereq' => 'INF-103'],
        ];
    }

    /** @return array<int, array{code: string, name: string, uc: int, sem: int, prereq?: string}> */
    private function subjectsCIV(): array
    {
        return [
            ['code' => 'CIV-101', 'name' => 'Cálculo I',                 'uc' => 4, 'sem' => 1],
            ['code' => 'CIV-102', 'name' => 'Física I',                  'uc' => 3, 'sem' => 1],
            ['code' => 'CIV-103', 'name' => 'Dibujo Técnico',            'uc' => 3, 'sem' => 1],
            ['code' => 'CIV-104', 'name' => 'Química General',           'uc' => 3, 'sem' => 1],
            ['code' => 'CIV-105', 'name' => 'Lenguaje y Comunicación',   'uc' => 2, 'sem' => 1],
            ['code' => 'CIV-201', 'name' => 'Cálculo II',                'uc' => 4, 'sem' => 2, 'prereq' => 'CIV-101'],
            ['code' => 'CIV-202', 'name' => 'Física II',                 'uc' => 3, 'sem' => 2, 'prereq' => 'CIV-102'],
            ['code' => 'CIV-203', 'name' => 'Mecánica Racional',         'uc' => 3, 'sem' => 2, 'prereq' => 'CIV-102'],
        ];
    }

    /** @return array<int, array{code: string, name: string, uc: int, sem: int, prereq?: string}> */
    private function subjectsCON(): array
    {
        return [
            ['code' => 'CON-101', 'name' => 'Contabilidad I',            'uc' => 4, 'sem' => 1],
            ['code' => 'CON-102', 'name' => 'Matemáticas Financieras I', 'uc' => 4, 'sem' => 1],
            ['code' => 'CON-103', 'name' => 'Economía General',          'uc' => 3, 'sem' => 1],
            ['code' => 'CON-104', 'name' => 'Derecho Mercantil',         'uc' => 2, 'sem' => 1],
            ['code' => 'CON-105', 'name' => 'Lenguaje y Comunicación',   'uc' => 2, 'sem' => 1],
            ['code' => 'CON-201', 'name' => 'Contabilidad II',           'uc' => 4, 'sem' => 2, 'prereq' => 'CON-101'],
            ['code' => 'CON-202', 'name' => 'Estadística I',             'uc' => 3, 'sem' => 2, 'prereq' => 'CON-102'],
            ['code' => 'CON-203', 'name' => 'Microeconomía',             'uc' => 3, 'sem' => 2, 'prereq' => 'CON-103'],
        ];
    }

    /** @return array<int, array{code: string, name: string, uc: int, sem: int, prereq?: string}> */
    private function subjectsADM(): array
    {
        return [
            ['code' => 'ADM-101', 'name' => 'Fundamentos de Administración', 'uc' => 3, 'sem' => 1],
            ['code' => 'ADM-102', 'name' => 'Matemáticas para Negocios',     'uc' => 4, 'sem' => 1],
            ['code' => 'ADM-103', 'name' => 'Economía General',              'uc' => 3, 'sem' => 1],
            ['code' => 'ADM-104', 'name' => 'Contabilidad I',                'uc' => 3, 'sem' => 1],
            ['code' => 'ADM-105', 'name' => 'Lenguaje y Comunicación',       'uc' => 2, 'sem' => 1],
            ['code' => 'ADM-201', 'name' => 'Administración I',              'uc' => 3, 'sem' => 2, 'prereq' => 'ADM-101'],
            ['code' => 'ADM-202', 'name' => 'Finanzas I',                    'uc' => 3, 'sem' => 2, 'prereq' => 'ADM-102'],
            ['code' => 'ADM-203', 'name' => 'Microeconomía',                 'uc' => 3, 'sem' => 2, 'prereq' => 'ADM-103'],
        ];
    }

    /** @return array<int, array{code: string, name: string, uc: int, sem: int, prereq?: string}> */
    private function subjectsEDU(): array
    {
        return [
            ['code' => 'EDU-101', 'name' => 'Cálculo I',                    'uc' => 4, 'sem' => 1],
            ['code' => 'EDU-102', 'name' => 'Álgebra',                      'uc' => 3, 'sem' => 1],
            ['code' => 'EDU-103', 'name' => 'Teoría de la Educación',       'uc' => 3, 'sem' => 1],
            ['code' => 'EDU-104', 'name' => 'Psicología del Aprendizaje',   'uc' => 3, 'sem' => 1],
            ['code' => 'EDU-105', 'name' => 'Lenguaje y Comunicación',      'uc' => 2, 'sem' => 1],
            ['code' => 'EDU-201', 'name' => 'Cálculo II',                   'uc' => 4, 'sem' => 2, 'prereq' => 'EDU-101'],
            ['code' => 'EDU-202', 'name' => 'Didáctica General',            'uc' => 3, 'sem' => 2, 'prereq' => 'EDU-103'],
            ['code' => 'EDU-203', 'name' => 'Geometría Analítica',          'uc' => 3, 'sem' => 2, 'prereq' => 'EDU-102'],
        ];
    }
}
```

- [ ] **Step 4: Correr test para verificar que pasa**

```bash
vendor/bin/sail artisan test --compact --filter="seeds academic structure"
```

Resultado esperado: PASS.

- [ ] **Step 5: Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add database/seeders/Demo/DemoAcademicSeeder.php tests/Feature/DemoSeederTest.php
git commit -m "feat: add DemoAcademicSeeder — 5 carreras, 40 materias, prelaciones"
```

---

## Task 3: DemoInfrastructureSeeder — edificios y aulas

**Files:**
- Modify: `database/seeders/Demo/DemoInfrastructureSeeder.php`
- Modify: `tests/Feature/DemoSeederTest.php`

- [ ] **Step 1: Agregar assertions al test**

En `tests/Feature/DemoSeederTest.php`, agregar un segundo test:

```php
it('seeds infrastructure', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Building::count())->toBeGreaterThanOrEqual(2);
    expect(\App\Models\Classroom::count())->toBeGreaterThanOrEqual(15);
});
```

- [ ] **Step 2: Correr test para verificar que falla**

```bash
vendor/bin/sail artisan test --compact --filter="seeds infrastructure"
```

Resultado esperado: FAIL.

- [ ] **Step 3: Implementar DemoInfrastructureSeeder**

```php
<?php

namespace Database\Seeders\Demo;

use App\Enums\ClassroomType;
use App\Models\Building;
use App\Models\Classroom;
use Illuminate\Database\Seeder;

class DemoInfrastructureSeeder extends Seeder
{
    public function run(): void
    {
        $edA = Building::firstOrCreate(['name' => 'Edificio A — Ingeniería']);
        $edB = Building::firstOrCreate(['name' => 'Edificio B — Ciencias y Humanidades']);

        $classrooms = [
            // Edificio A
            ['building' => $edA, 'identifier' => 'A-101', 'type' => ClassroomType::Theory,     'capacity' => 40],
            ['building' => $edA, 'identifier' => 'A-102', 'type' => ClassroomType::Theory,     'capacity' => 35],
            ['building' => $edA, 'identifier' => 'A-103', 'type' => ClassroomType::Theory,     'capacity' => 30],
            ['building' => $edA, 'identifier' => 'A-201', 'type' => ClassroomType::Theory,     'capacity' => 35],
            ['building' => $edA, 'identifier' => 'A-202', 'type' => ClassroomType::Theory,     'capacity' => 30],
            ['building' => $edA, 'identifier' => 'A-Lab1','type' => ClassroomType::Laboratory, 'capacity' => 25],
            ['building' => $edA, 'identifier' => 'A-Lab2','type' => ClassroomType::Laboratory, 'capacity' => 20],
            // Edificio B
            ['building' => $edB, 'identifier' => 'B-101', 'type' => ClassroomType::Theory,     'capacity' => 40],
            ['building' => $edB, 'identifier' => 'B-102', 'type' => ClassroomType::Theory,     'capacity' => 35],
            ['building' => $edB, 'identifier' => 'B-103', 'type' => ClassroomType::Theory,     'capacity' => 30],
            ['building' => $edB, 'identifier' => 'B-104', 'type' => ClassroomType::Theory,     'capacity' => 25],
            ['building' => $edB, 'identifier' => 'B-201', 'type' => ClassroomType::Theory,     'capacity' => 35],
            ['building' => $edB, 'identifier' => 'B-202', 'type' => ClassroomType::Theory,     'capacity' => 30],
            ['building' => $edB, 'identifier' => 'B-Lab1','type' => ClassroomType::Laboratory, 'capacity' => 20],
            ['building' => $edB, 'identifier' => 'B-Lab2','type' => ClassroomType::Laboratory, 'capacity' => 20],
        ];

        foreach ($classrooms as $data) {
            Classroom::firstOrCreate(
                ['identifier' => $data['identifier']],
                [
                    'building_id' => $data['building']->id,
                    'type'        => $data['type']->value,
                    'capacity'    => $data['capacity'],
                ],
            );
        }
    }
}
```

- [ ] **Step 4: Correr test**

```bash
vendor/bin/sail artisan test --compact --filter="seeds infrastructure"
```

Resultado esperado: PASS.

- [ ] **Step 5: Pint + Commit**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add database/seeders/Demo/DemoInfrastructureSeeder.php tests/Feature/DemoSeederTest.php
git commit -m "feat: add DemoInfrastructureSeeder — 2 edificios, 15 aulas"
```

---

## Task 4: DemoPeriodSeeder — períodos y lapsos

**Files:**
- Modify: `database/seeders/Demo/DemoPeriodSeeder.php`
- Modify: `tests/Feature/DemoSeederTest.php`

- [ ] **Step 1: Agregar assertions al test**

```php
it('seeds periods', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Period::count())->toBeGreaterThanOrEqual(2);
    expect(\App\Models\Lapse::count())->toBeGreaterThanOrEqual(4);

    $active = \App\Models\Period::where('status', 'active')->first();
    expect($active)->not->toBeNull();
    expect($active->name)->toBe('2026-I');
});
```

- [ ] **Step 2: Correr test para verificar que falla**

```bash
vendor/bin/sail artisan test --compact --filter="seeds periods"
```

- [ ] **Step 3: Implementar DemoPeriodSeeder**

```php
<?php

namespace Database\Seeders\Demo;

use App\Enums\PeriodStatus;
use App\Enums\PeriodType;
use App\Models\Lapse;
use App\Models\Period;
use Illuminate\Database\Seeder;

class DemoPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $p2025II = Period::firstOrCreate(
            ['name' => '2025-II'],
            [
                'type'       => PeriodType::Semester,
                'start_date' => '2025-07-14',
                'end_date'   => '2026-01-16',
                'status'     => PeriodStatus::Closed,
            ],
        );

        $p2026I = Period::firstOrCreate(
            ['name' => '2026-I'],
            [
                'type'       => PeriodType::Semester,
                'start_date' => '2026-01-19',
                'end_date'   => '2026-07-17',
                'status'     => PeriodStatus::Active,
            ],
        );

        $lapses = [
            ['period' => $p2025II, 'number' => 1, 'name' => 'Primer Lapso',  'start' => '2025-07-14', 'end' => '2025-10-10'],
            ['period' => $p2025II, 'number' => 2, 'name' => 'Segundo Lapso', 'start' => '2025-10-13', 'end' => '2026-01-16'],
            ['period' => $p2026I,  'number' => 1, 'name' => 'Primer Lapso',  'start' => '2026-01-19', 'end' => '2026-04-10'],
            ['period' => $p2026I,  'number' => 2, 'name' => 'Segundo Lapso', 'start' => '2026-04-13', 'end' => '2026-07-17'],
        ];

        foreach ($lapses as $data) {
            Lapse::firstOrCreate(
                ['period_id' => $data['period']->id, 'number' => $data['number']],
                ['name' => $data['name'], 'start_date' => $data['start'], 'end_date' => $data['end']],
            );
        }
    }
}
```

- [ ] **Step 4: Correr test + Pint + Commit**

```bash
vendor/bin/sail artisan test --compact --filter="seeds periods"
vendor/bin/sail bin pint --dirty --format agent
git add database/seeders/Demo/DemoPeriodSeeder.php tests/Feature/DemoSeederTest.php
git commit -m "feat: add DemoPeriodSeeder — 2 períodos semestres, 4 lapsos"
```

---

## Task 5: DemoProfessorsSeeder — 12 profesores con usuarios

**Files:**
- Modify: `database/seeders/Demo/DemoProfessorsSeeder.php`
- Modify: `tests/Feature/DemoSeederTest.php`

- [ ] **Step 1: Agregar assertions al test**

```php
it('seeds professors', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Professor::count())->toBeGreaterThanOrEqual(12);

    $prof = \App\Models\User::where('email', 'prof01@utcacao.edu.ve')->first();
    expect($prof)->not->toBeNull();
    expect($prof->hasRole('Profesor'))->toBeTrue();
});
```

- [ ] **Step 2: Correr test para verificar que falla**

```bash
vendor/bin/sail artisan test --compact --filter="seeds professors"
```

- [ ] **Step 3: Implementar DemoProfessorsSeeder**

```php
<?php

namespace Database\Seeders\Demo;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoProfessorsSeeder extends Seeder
{
    private const PROFESSORS = [
        ['num' => '01', 'name' => 'Carlos Mendoza'],
        ['num' => '02', 'name' => 'Ana García'],
        ['num' => '03', 'name' => 'Luis Rodríguez'],
        ['num' => '04', 'name' => 'María Torres'],
        ['num' => '05', 'name' => 'Pedro Gómez'],
        ['num' => '06', 'name' => 'Carmen López'],
        ['num' => '07', 'name' => 'José Martínez'],
        ['num' => '08', 'name' => 'Elena Vargas'],
        ['num' => '09', 'name' => 'Roberto Díaz'],
        ['num' => '10', 'name' => 'Patricia Sánchez'],
        ['num' => '11', 'name' => 'Miguel Herrera'],
        ['num' => '12', 'name' => 'Laura Morales'],
    ];

    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Profesor', 'guard_name' => 'web']);

        foreach (self::PROFESSORS as $data) {
            $email = "prof{$data['num']}@utcacao.edu.ve";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$role->name]);

            Professor::firstOrCreate(
                ['user_id' => $user->id],
                ['weekly_hour_limit' => 20, 'active' => true],
            );
        }
    }
}
```

- [ ] **Step 4: Correr test + Pint + Commit**

```bash
vendor/bin/sail artisan test --compact --filter="seeds professors"
vendor/bin/sail bin pint --dirty --format agent
git add database/seeders/Demo/DemoProfessorsSeeder.php tests/Feature/DemoSeederTest.php
git commit -m "feat: add DemoProfessorsSeeder — 12 profesores con usuarios"
```

---

## Task 6: DemoStudentsSeeder — 120 universitarios + 20 secundaria con representantes

**Files:**
- Modify: `database/seeders/Demo/DemoStudentsSeeder.php`
- Modify: `tests/Feature/DemoSeederTest.php`

**Distribución:** 24 estudiantes por pensum × 5 carreras = 120 universitarios. Números est001–est120. Los 20 secundaria: sec01–sec20 (4 por año 1-5).

- [ ] **Step 1: Agregar assertions al test**

```php
it('seeds students', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Student::count())->toBeGreaterThanOrEqual(140);
    expect(\App\Models\Guardian::count())->toBeGreaterThanOrEqual(20);

    $uni = \App\Models\User::where('email', 'est001@utcacao.edu.ve')->first();
    expect($uni)->not->toBeNull();
    expect($uni->hasRole('Estudiante'))->toBeTrue();
    expect($uni->student->current_pensum_id)->not->toBeNull();

    $sec = \App\Models\User::where('email', 'sec01@utcacao.edu.ve')->first();
    expect($sec)->not->toBeNull();
    expect($sec->student->guardian_id)->not->toBeNull();
});
```

- [ ] **Step 2: Correr test para verificar que falla**

```bash
vendor/bin/sail artisan test --compact --filter="seeds students"
```

- [ ] **Step 3: Implementar DemoStudentsSeeder**

```php
<?php

namespace Database\Seeders\Demo;

use App\Enums\EducationalLevel;
use App\Models\Career;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoStudentsSeeder extends Seeder
{
    private const CAREER_CODES = ['INF', 'CIV', 'CON', 'ADM', 'EDU'];

    private const SECONDARY_YEARS = [1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5];

    public function run(): void
    {
        $studentRole    = Role::firstOrCreate(['name' => 'Estudiante',   'guard_name' => 'web']);
        $guardianRole   = Role::firstOrCreate(['name' => 'Representante','guard_name' => 'web']);

        $this->seedUniversityStudents($studentRole);
        $this->seedSecondaryStudents($studentRole, $guardianRole);
    }

    private function seedUniversityStudents(Role $role): void
    {
        $pensums = [];
        foreach (self::CAREER_CODES as $code) {
            $career = Career::where('code', $code)->first();
            if ($career) {
                $pensums[$code] = Pensum::where('career_id', $career->id)->where('is_active', true)->first();
            }
        }

        $number = 1;
        foreach (self::CAREER_CODES as $code) {
            $pensum = $pensums[$code] ?? null;

            for ($i = 0; $i < 24; $i++) {
                $email       = sprintf('est%03d@utcacao.edu.ve', $number);
                $academicYear = ($i % 4) + 1; // 6 students per year 1-4

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'              => fake()->name(),
                        'password'          => Hash::make('password'),
                        'email_verified_at' => now(),
                    ],
                );
                $user->syncRoles([$role->name]);

                Student::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'guardian_id'       => null,
                        'educational_level' => EducationalLevel::University,
                        'current_pensum_id' => $pensum?->id,
                        'academic_year'     => $academicYear,
                    ],
                );

                $number++;
            }
        }
    }

    private function seedSecondaryStudents(
        Role $studentRole,
        Role $guardianRole,
    ): void {
        foreach (self::SECONDARY_YEARS as $i => $year) {
            $num         = $i + 1;
            $repEmail    = sprintf('rep%02d@utcacao.edu.ve', $num);
            $studEmail   = sprintf('sec%02d@utcacao.edu.ve', $num);

            $repUser = User::firstOrCreate(
                ['email' => $repEmail],
                [
                    'name'              => fake()->name(),
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
            $repUser->syncRoles([$guardianRole->name]);

            $guardian = Guardian::firstOrCreate(['user_id' => $repUser->id]);

            $studUser = User::firstOrCreate(
                ['email' => $studEmail],
                [
                    'name'              => fake()->name(),
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
            $studUser->syncRoles([$studentRole->name]);

            Student::firstOrCreate(
                ['user_id' => $studUser->id],
                [
                    'guardian_id'       => $guardian->id,
                    'educational_level' => EducationalLevel::Secondary,
                    'current_pensum_id' => null,
                    'academic_year'     => $year,
                ],
            );
        }
    }
}
```

- [ ] **Step 4: Correr test + Pint + Commit**

```bash
vendor/bin/sail artisan test --compact --filter="seeds students"
vendor/bin/sail bin pint --dirty --format agent
git add database/seeders/Demo/DemoStudentsSeeder.php tests/Feature/DemoSeederTest.php
git commit -m "feat: add DemoStudentsSeeder — 120 universitarios, 20 secundaria, 20 representantes"
```

---

## Task 7: DemoSectionsSeeder — secciones universitarias + horarios

**Files:**
- Modify: `database/seeders/Demo/DemoSectionsSeeder.php`
- Modify: `tests/Feature/DemoSeederTest.php`

**Lógica:** Para el período 2026-I, crear 2 secciones (A y B) por cada materia de semestre 1, y 1 sección (A) por cada materia de semestre 2. Cada sección recibe 2 horarios (Mon+Wed o Tue+Thu). Rotar profesores por índice.

- [ ] **Step 1: Agregar assertions al test**

```php
it('seeds sections and schedules', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Section::count())->toBeGreaterThanOrEqual(60);
    expect(\App\Models\Schedule::count())->toBeGreaterThanOrEqual(120);
});
```

- [ ] **Step 2: Correr test para verificar que falla**

```bash
vendor/bin/sail artisan test --compact --filter="seeds sections and schedules"
```

- [ ] **Step 3: Implementar DemoSectionsSeeder**

```php
<?php

namespace Database\Seeders\Demo;

use App\Enums\DayOfWeek;
use App\Enums\ScheduleSessionType;
use App\Enums\SectionType;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Professor;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DemoSectionsSeeder extends Seeder
{
    /** @var array<int, array{0: DayOfWeek, 1: DayOfWeek}> */
    private const SCHEDULE_PAIRS = [
        [DayOfWeek::Monday,  DayOfWeek::Wednesday],
        [DayOfWeek::Tuesday, DayOfWeek::Thursday],
        [DayOfWeek::Monday,  DayOfWeek::Wednesday],
        [DayOfWeek::Tuesday, DayOfWeek::Thursday],
        [DayOfWeek::Monday,  DayOfWeek::Friday],
    ];

    private const TIME_SLOTS = [
        ['start' => '07:00:00', 'end' => '09:30:00'],
        ['start' => '09:30:00', 'end' => '12:00:00'],
        ['start' => '13:00:00', 'end' => '15:30:00'],
        ['start' => '15:30:00', 'end' => '18:00:00'],
    ];

    public function run(): void
    {
        $period = Period::where('name', '2026-I')->first();
        if (! $period) {
            return;
        }

        $professors = Professor::with('user')->get();
        $classrooms = Classroom::where('type', 'theory')->get();

        if ($professors->isEmpty() || $classrooms->isEmpty()) {
            return;
        }

        $subjects = Subject::whereIn('period_number', [1, 2])->orderBy('code')->get();

        $profIndex  = 0;
        $roomIndex  = 0;
        $slotIndex  = 0;
        $pairIndex  = 0;

        foreach ($subjects as $subject) {
            $sectionCount = $subject->period_number === 1 ? 2 : 1;

            for ($s = 0; $s < $sectionCount; $s++) {
                $sectionCode = chr(65 + $s); // 'A' or 'B'

                $section = Section::firstOrCreate(
                    ['period_id' => $period->id, 'subject_id' => $subject->id, 'code' => $sectionCode],
                    [
                        'type'                => SectionType::University,
                        'theory_classroom_id' => $classrooms[$roomIndex % $classrooms->count()]->id,
                        'lab_classroom_id'    => null,
                        'capacity'            => 30,
                    ],
                );

                $pair = self::SCHEDULE_PAIRS[$pairIndex % count(self::SCHEDULE_PAIRS)];
                $slot = self::TIME_SLOTS[$slotIndex % count(self::TIME_SLOTS)];
                $prof = $professors[$profIndex % $professors->count()];
                $room = $classrooms[$roomIndex % $classrooms->count()];

                foreach ($pair as $day) {
                    Schedule::firstOrCreate(
                        [
                            'section_id'  => $section->id,
                            'day_of_week' => $day->value,
                        ],
                        [
                            'professor_id' => $prof->id,
                            'classroom_id' => $room->id,
                            'subject_id'   => $subject->id,
                            'start_time'   => $slot['start'],
                            'end_time'     => $slot['end'],
                            'type'         => ScheduleSessionType::Theory->value,
                            'valid_from'   => $period->start_date,
                            'valid_until'  => null,
                        ],
                    );
                }

                $profIndex++;
                $roomIndex++;
                $slotIndex++;
                $pairIndex++;
            }
        }
    }
}
```

- [ ] **Step 4: Correr test + Pint + Commit**

```bash
vendor/bin/sail artisan test --compact --filter="seeds sections and schedules"
vendor/bin/sail bin pint --dirty --format agent
git add database/seeders/Demo/DemoSectionsSeeder.php tests/Feature/DemoSeederTest.php
git commit -m "feat: add DemoSectionsSeeder — secciones 2026-I con horarios"
```

---

## Task 8: DemoEnrollmentSeeder — inscripciones con estados variados

**Files:**
- Modify: `database/seeders/Demo/DemoEnrollmentSeeder.php`
- Modify: `tests/Feature/DemoSeederTest.php`

**Lógica:** Tomar los primeros 90 estudiantes universitarios (que tienen `current_pensum_id`). Dividirlos en 3 grupos de 30 por estado. Cada enrollment tiene 4-5 `EnrollmentDetail` usando las secciones semestre-1 de su pensum en el período 2026-I.

- [ ] **Step 1: Agregar assertions al test**

```php
it('seeds enrollments', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Enrollment::where('status', 'draft')->count())->toBeGreaterThanOrEqual(25);
    expect(\App\Models\Enrollment::where('status', 'confirmed')->count())->toBeGreaterThanOrEqual(25);
    expect(\App\Models\Enrollment::where('status', 'approved')->count())->toBeGreaterThanOrEqual(25);
    expect(\App\Models\EnrollmentDetail::count())->toBeGreaterThanOrEqual(270);
});
```

- [ ] **Step 2: Correr test para verificar que falla**

```bash
vendor/bin/sail artisan test --compact --filter="seeds enrollments"
```

- [ ] **Step 3: Implementar DemoEnrollmentSeeder**

```php
<?php

namespace Database\Seeders\Demo;

use App\Enums\EnrollmentDetailStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentDetail;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DemoEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $period = Period::where('name', '2026-I')->first();
        if (! $period) {
            return;
        }

        $students = Student::whereNotNull('current_pensum_id')
            ->with(['user'])
            ->take(90)
            ->get();

        $groups = [
            ['status' => EnrollmentStatus::Draft,     'detailStatus' => EnrollmentDetailStatus::Draft,     'students' => $students->slice(0, 30)],
            ['status' => EnrollmentStatus::Confirmed,  'detailStatus' => EnrollmentDetailStatus::Confirmed, 'students' => $students->slice(30, 30)],
            ['status' => EnrollmentStatus::Approved,   'detailStatus' => EnrollmentDetailStatus::Confirmed, 'students' => $students->slice(60, 30)],
        ];

        foreach ($groups as $group) {
            foreach ($group['students'] as $student) {
                $this->seedEnrollment($student, $period, $group['status'], $group['detailStatus']);
            }
        }
    }

    private function seedEnrollment(
        Student $student,
        Period $period,
        EnrollmentStatus $status,
        EnrollmentDetailStatus $detailStatus,
    ): void {
        // Get sections from student's pensum, semester 1, in this period
        $sections = Section::where('period_id', $period->id)
            ->whereHas('subject', fn ($q) => $q
                ->where('pensum_id', $student->current_pensum_id)
                ->where('period_number', 1))
            ->with('subject')
            ->get();

        if ($sections->isEmpty()) {
            return;
        }

        // Take 4-5 sections per student (avoid duplicate subject_id)
        $picked = $sections->unique('subject_id')->take(4);

        $ucInscritas = $picked->sum(fn ($sec) => $sec->subject->credits_uc);

        $enrollment = Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'period_id' => $period->id],
            [
                'pensum_id'      => $student->current_pensum_id,
                'uc_disponibles' => 20,
                'uc_inscritas'   => $ucInscritas,
                'status'         => $status->value,
            ],
        );

        foreach ($picked as $section) {
            EnrollmentDetail::firstOrCreate(
                ['enrollment_id' => $enrollment->id, 'subject_id' => $section->subject_id],
                [
                    'section_id' => $section->id,
                    'status'     => $detailStatus->value,
                ],
            );
        }
    }
}
```

- [ ] **Step 4: Correr test + Pint + Commit**

```bash
vendor/bin/sail artisan test --compact --filter="seeds enrollments"
vendor/bin/sail bin pint --dirty --format agent
git add database/seeders/Demo/DemoEnrollmentSeeder.php tests/Feature/DemoSeederTest.php
git commit -m "feat: add DemoEnrollmentSeeder — 90 inscripciones (30 draft/confirmed/approved)"
```

---

## Task 9: DemoSeederTest completo — conteos totales + idempotencia

**Files:**
- Modify: `tests/Feature/DemoSeederTest.php`
- Modify: `feature_list.json` (marcar completed)

- [ ] **Step 1: Reemplazar `tests/Feature/DemoSeederTest.php` con versión completa**

```php
<?php

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds academic structure', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\CareerCategory::count())->toBeGreaterThanOrEqual(3);
    expect(\App\Models\Career::count())->toBeGreaterThanOrEqual(5);
    expect(\App\Models\Pensum::count())->toBeGreaterThanOrEqual(5);
    expect(\App\Models\Subject::count())->toBeGreaterThanOrEqual(40);
});

it('seeds infrastructure', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Building::count())->toBeGreaterThanOrEqual(2);
    expect(\App\Models\Classroom::count())->toBeGreaterThanOrEqual(15);
});

it('seeds periods', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Period::count())->toBeGreaterThanOrEqual(2);
    expect(\App\Models\Lapse::count())->toBeGreaterThanOrEqual(4);

    $active = \App\Models\Period::where('status', 'active')->first();
    expect($active)->not->toBeNull();
    expect($active->name)->toBe('2026-I');
});

it('seeds professors', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Professor::count())->toBeGreaterThanOrEqual(12);

    $prof = \App\Models\User::where('email', 'prof01@utcacao.edu.ve')->first();
    expect($prof)->not->toBeNull();
    expect($prof->hasRole('Profesor'))->toBeTrue();
});

it('seeds students', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Student::count())->toBeGreaterThanOrEqual(140);
    expect(\App\Models\Guardian::count())->toBeGreaterThanOrEqual(20);

    $uni = \App\Models\User::where('email', 'est001@utcacao.edu.ve')->first();
    expect($uni)->not->toBeNull();
    expect($uni->hasRole('Estudiante'))->toBeTrue();
    expect($uni->student->current_pensum_id)->not->toBeNull();

    $sec = \App\Models\User::where('email', 'sec01@utcacao.edu.ve')->first();
    expect($sec)->not->toBeNull();
    expect($sec->student->guardian_id)->not->toBeNull();
});

it('seeds sections and schedules', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Section::count())->toBeGreaterThanOrEqual(60);
    expect(\App\Models\Schedule::count())->toBeGreaterThanOrEqual(120);
});

it('seeds enrollments', function () {
    (new DemoSeeder())->run();

    expect(\App\Models\Enrollment::where('status', 'draft')->count())->toBeGreaterThanOrEqual(25);
    expect(\App\Models\Enrollment::where('status', 'confirmed')->count())->toBeGreaterThanOrEqual(25);
    expect(\App\Models\Enrollment::where('status', 'approved')->count())->toBeGreaterThanOrEqual(25);
    expect(\App\Models\EnrollmentDetail::count())->toBeGreaterThanOrEqual(270);
});

it('is idempotent — running twice does not duplicate data', function () {
    (new DemoSeeder())->run();
    $counts = [
        'categories' => \App\Models\CareerCategory::count(),
        'subjects'   => \App\Models\Subject::count(),
        'professors' => \App\Models\Professor::count(),
        'students'   => \App\Models\Student::count(),
        'sections'   => \App\Models\Section::count(),
        'enrollments'=> \App\Models\Enrollment::count(),
    ];

    (new DemoSeeder())->run();

    expect(\App\Models\CareerCategory::count())->toBe($counts['categories']);
    expect(\App\Models\Subject::count())->toBe($counts['subjects']);
    expect(\App\Models\Professor::count())->toBe($counts['professors']);
    expect(\App\Models\Student::count())->toBe($counts['students']);
    expect(\App\Models\Section::count())->toBe($counts['sections']);
    expect(\App\Models\Enrollment::count())->toBe($counts['enrollments']);
});
```

- [ ] **Step 2: Correr suite completa**

```bash
vendor/bin/sail artisan test --compact --filter=DemoSeeder
```

Resultado esperado: todos los tests PASS, incluyendo el de idempotencia.

- [ ] **Step 3: Correr DemoSeeder en BD real para verificar**

```bash
vendor/bin/sail artisan migrate:fresh && vendor/bin/sail artisan db:seed --class=DemoSeeder
```

Resultado esperado: sin errores, termina con mensaje de cada sub-seeder.

- [ ] **Step 4: Marcar feature como completed en feature_list.json**

```json
{
  "id": "03-demo-seeder",
  "name": "Demo Seeder — datos realistas venezolanos para demo y desarrollo",
  "status": "completed",
  "current_task": 9,
  "plan": "docs/superpowers/plans/2026-05-21-demo-seeder.md"
}
```

- [ ] **Step 5: Pint + Commit final**

```bash
vendor/bin/sail bin pint --dirty --format agent
git add tests/Feature/DemoSeederTest.php feature_list.json
git commit -m "feat: complete DemoSeeder — 8 idempotent sub-seeders, full test suite"
```

---

## Credenciales del demo

| Rol | Email | Password |
|-----|-------|----------|
| Admin | admin@cacao.edu.ve | password |
| Coordinador | coordinador@cacao.edu.ve | password |
| Profesor (×12) | prof01–prof12@utcacao.edu.ve | password |
| Estudiante universitario (×120) | est001–est120@utcacao.edu.ve | password |
| Estudiante secundaria (×20) | sec01–sec20@utcacao.edu.ve | password |
| Representante (×20) | rep01–rep20@utcacao.edu.ve | password |

**Para probar el módulo de inscripciones:** iniciar sesión como `est001@utcacao.edu.ve` → ir a `/enrollment`.
