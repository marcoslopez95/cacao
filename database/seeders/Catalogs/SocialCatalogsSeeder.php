<?php

namespace Database\Seeders\Catalogs;

use App\Models\Catalogs\DigitalLevel;
use App\Models\Catalogs\HouseholdHeadType;
use App\Models\Catalogs\InstitutionType;
use App\Models\Catalogs\KinshipType;
use App\Models\Catalogs\Language;
use App\Models\Catalogs\LanguageLevel;
use App\Models\Catalogs\LivingArrangement;
use App\Models\Catalogs\MaritalStatus;
use App\Models\Catalogs\Religion;
use App\Models\Catalogs\TransferReason;
use Illuminate\Database\Seeder;

class SocialCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedKinshipTypes();
        $this->seedMaritalStatuses();
        $this->seedInstitutionTypes();
        $this->seedTransferReasons();
        $this->seedDigitalLevels();
        $this->seedLanguageLevels();
        $this->seedLanguages();
        $this->seedLivingArrangements();
        $this->seedHouseholdHeadTypes();
        $this->seedReligions();
    }

    private function seedKinshipTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'father', 'name' => 'Padre', 'sort_order' => 1],
            ['code' => 'mother', 'name' => 'Madre', 'sort_order' => 2],
            ['code' => 'legal_guardian', 'name' => 'Tutor legal', 'sort_order' => 3],
            ['code' => 'grandparent', 'name' => 'Abuelo/a', 'sort_order' => 4],
            ['code' => 'uncle', 'name' => 'Tío/a', 'sort_order' => 5],
            ['code' => 'sibling', 'name' => 'Hermano/a', 'sort_order' => 6],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 7],
        ];

        foreach ($types as $type) {
            KinshipType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedMaritalStatuses(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $statuses
         */
        $statuses = [
            ['code' => 'single', 'name' => 'Soltero/a', 'sort_order' => 1],
            ['code' => 'married', 'name' => 'Casado/a', 'sort_order' => 2],
            ['code' => 'divorced', 'name' => 'Divorciado/a', 'sort_order' => 3],
            ['code' => 'widowed', 'name' => 'Viudo/a', 'sort_order' => 4],
            ['code' => 'civil_union', 'name' => 'Unión libre', 'sort_order' => 5],
        ];

        foreach ($statuses as $status) {
            MaritalStatus::firstOrCreate(
                ['code' => $status['code']],
                ['name' => $status['name'], 'active' => true, 'sort_order' => $status['sort_order']],
            );
        }
    }

    private function seedInstitutionTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'public', 'name' => 'Pública', 'sort_order' => 1],
            ['code' => 'private', 'name' => 'Privada', 'sort_order' => 2],
            ['code' => 'fe_y_alegria', 'name' => 'Fe y Alegría', 'sort_order' => 3],
            ['code' => 'other', 'name' => 'Otra', 'sort_order' => 4],
        ];

        foreach ($types as $type) {
            InstitutionType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedTransferReasons(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $reasons
         */
        $reasons = [
            ['code' => 'relocation', 'name' => 'Cambio de residencia', 'sort_order' => 1],
            ['code' => 'economic', 'name' => 'Razones económicas', 'sort_order' => 2],
            ['code' => 'academic_performance', 'name' => 'Rendimiento académico', 'sort_order' => 3],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 4],
        ];

        foreach ($reasons as $reason) {
            TransferReason::firstOrCreate(
                ['code' => $reason['code']],
                ['name' => $reason['name'], 'active' => true, 'sort_order' => $reason['sort_order']],
            );
        }
    }

    private function seedDigitalLevels(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $levels
         */
        $levels = [
            ['code' => 'none', 'name' => 'Sin conocimiento', 'sort_order' => 1],
            ['code' => 'basic', 'name' => 'Básico', 'sort_order' => 2],
            ['code' => 'intermediate', 'name' => 'Intermedio', 'sort_order' => 3],
            ['code' => 'advanced', 'name' => 'Avanzado', 'sort_order' => 4],
        ];

        foreach ($levels as $level) {
            DigitalLevel::firstOrCreate(
                ['code' => $level['code']],
                ['name' => $level['name'], 'active' => true, 'sort_order' => $level['sort_order']],
            );
        }
    }

    private function seedLanguageLevels(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $levels
         */
        $levels = [
            ['code' => 'a1', 'name' => 'A1', 'sort_order' => 1],
            ['code' => 'a2', 'name' => 'A2', 'sort_order' => 2],
            ['code' => 'b1', 'name' => 'B1', 'sort_order' => 3],
            ['code' => 'b2', 'name' => 'B2', 'sort_order' => 4],
            ['code' => 'c1', 'name' => 'C1', 'sort_order' => 5],
            ['code' => 'c2', 'name' => 'C2', 'sort_order' => 6],
            ['code' => 'native', 'name' => 'Nativo/a', 'sort_order' => 7],
        ];

        foreach ($levels as $level) {
            LanguageLevel::firstOrCreate(
                ['code' => $level['code']],
                ['name' => $level['name'], 'active' => true, 'sort_order' => $level['sort_order']],
            );
        }
    }

    private function seedLanguages(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $languages
         */
        $languages = [
            ['code' => 'es', 'name' => 'Español', 'sort_order' => 1],
            ['code' => 'en', 'name' => 'Inglés', 'sort_order' => 2],
            ['code' => 'fr', 'name' => 'Francés', 'sort_order' => 3],
            ['code' => 'pt', 'name' => 'Portugués', 'sort_order' => 4],
            ['code' => 'de', 'name' => 'Alemán', 'sort_order' => 5],
            ['code' => 'it', 'name' => 'Italiano', 'sort_order' => 6],
            ['code' => 'zh', 'name' => 'Chino', 'sort_order' => 7],
            ['code' => 'ar', 'name' => 'Árabe', 'sort_order' => 8],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 9],
        ];

        foreach ($languages as $language) {
            Language::firstOrCreate(
                ['code' => $language['code']],
                ['name' => $language['name'], 'active' => true, 'sort_order' => $language['sort_order']],
            );
        }
    }

    private function seedLivingArrangements(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $arrangements
         */
        $arrangements = [
            ['code' => 'both_parents', 'name' => 'Ambos padres', 'sort_order' => 1],
            ['code' => 'mother_only', 'name' => 'Solo con la madre', 'sort_order' => 2],
            ['code' => 'father_only', 'name' => 'Solo con el padre', 'sort_order' => 3],
            ['code' => 'relative', 'name' => 'Con un familiar', 'sort_order' => 4],
            ['code' => 'independent', 'name' => 'Independiente', 'sort_order' => 5],
            ['code' => 'student_residence', 'name' => 'Residencia estudiantil', 'sort_order' => 6],
        ];

        foreach ($arrangements as $arrangement) {
            LivingArrangement::firstOrCreate(
                ['code' => $arrangement['code']],
                ['name' => $arrangement['name'], 'active' => true, 'sort_order' => $arrangement['sort_order']],
            );
        }
    }

    private function seedHouseholdHeadTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'father', 'name' => 'Padre', 'sort_order' => 1],
            ['code' => 'mother', 'name' => 'Madre', 'sort_order' => 2],
            ['code' => 'student', 'name' => 'El/la estudiante', 'sort_order' => 3],
            ['code' => 'other_relative', 'name' => 'Otro familiar', 'sort_order' => 4],
        ];

        foreach ($types as $type) {
            HouseholdHeadType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedReligions(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $religions
         */
        $religions = [
            ['code' => 'catholic', 'name' => 'Católico', 'sort_order' => 1],
            ['code' => 'evangelical', 'name' => 'Evangélico', 'sort_order' => 2],
            ['code' => 'protestant', 'name' => 'Protestante', 'sort_order' => 3],
            ['code' => 'jewish', 'name' => 'Judío', 'sort_order' => 4],
            ['code' => 'muslim', 'name' => 'Musulmán', 'sort_order' => 5],
            ['code' => 'agnostic', 'name' => 'Agnóstico', 'sort_order' => 6],
            ['code' => 'atheist', 'name' => 'Ateo', 'sort_order' => 7],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 8],
        ];

        foreach ($religions as $religion) {
            Religion::firstOrCreate(
                ['code' => $religion['code']],
                [
                    'name' => $religion['name'],
                    'active' => true,
                    'sort_order' => $religion['sort_order'],
                    'description' => 'Campo opcional. Solo para estadísticas institucionales.',
                ],
            );
        }
    }
}
