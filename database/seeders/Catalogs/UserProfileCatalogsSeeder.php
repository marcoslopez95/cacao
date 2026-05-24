<?php

namespace Database\Seeders\Catalogs;

use App\Models\Catalogs\AttachmentDocumentType;
use App\Models\Catalogs\DocumentType;
use App\Models\Catalogs\Gender;
use App\Models\Catalogs\GeographicZone;
use Illuminate\Database\Seeder;

class UserProfileCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGenders();
        $this->seedDocumentTypes();
        $this->seedGeographicZones();
        $this->seedAttachmentDocumentTypes();
    }

    private function seedGenders(): void
    {
        /**
         * @var array<int, array{code: string, name: string}> $genders
         */
        $genders = [
            ['code' => 'male', 'name' => 'Masculino'],
            ['code' => 'female', 'name' => 'Femenino'],
            ['code' => 'non_binary', 'name' => 'No binario'],
            ['code' => 'prefer_not_to_say', 'name' => 'Prefiero no indicar'],
        ];

        foreach ($genders as $gender) {
            Gender::firstOrCreate(
                ['code' => $gender['code']],
                ['name' => $gender['name'], 'active' => true, 'sort_order' => 0],
            );
        }
    }

    private function seedDocumentTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string}> $types
         */
        $types = [
            ['code' => 'V', 'name' => 'Venezolano'],
            ['code' => 'E', 'name' => 'Extranjero'],
            ['code' => 'P', 'name' => 'Pasaporte'],
            ['code' => 'J', 'name' => 'Jurídico'],
        ];

        foreach ($types as $type) {
            DocumentType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => 0],
            );
        }
    }

    private function seedGeographicZones(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $zones
         */
        $zones = [
            ['code' => 'urban', 'name' => 'Urbano', 'sort_order' => 1],
            ['code' => 'periurban', 'name' => 'Periurbano', 'sort_order' => 2],
            ['code' => 'rural', 'name' => 'Rural', 'sort_order' => 3],
        ];

        foreach ($zones as $zone) {
            GeographicZone::firstOrCreate(
                ['code' => $zone['code']],
                ['name' => $zone['name'], 'active' => true, 'sort_order' => $zone['sort_order']],
            );
        }
    }

    private function seedAttachmentDocumentTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string}> $types
         */
        $types = [
            ['code' => 'id_card', 'name' => 'Cédula de identidad'],
            ['code' => 'birth_certificate', 'name' => 'Acta de nacimiento'],
            ['code' => 'academic_title', 'name' => 'Título académico'],
            ['code' => 'transcript', 'name' => 'Notas certificadas'],
            ['code' => 'passport', 'name' => 'Pasaporte'],
            ['code' => 'other', 'name' => 'Otro'],
        ];

        foreach ($types as $type) {
            AttachmentDocumentType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => 0],
            );
        }
    }
}
