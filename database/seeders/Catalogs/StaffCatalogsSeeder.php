<?php

namespace Database\Seeders\Catalogs;

use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\EmploymentStatus;
use Illuminate\Database\Seeder;

class StaffCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedContractTypes();
        $this->seedDedicationTypes();
        $this->seedEmploymentStatuses();
    }

    private function seedContractTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'permanent', 'name' => 'Fijo', 'sort_order' => 1],
            ['code' => 'contracted', 'name' => 'Contratado', 'sort_order' => 2],
            ['code' => 'hourly', 'name' => 'Por horas', 'sort_order' => 3],
            ['code' => 'honoraria', 'name' => 'Honorarios profesionales', 'sort_order' => 4],
        ];

        foreach ($types as $type) {
            ContractType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedDedicationTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'full_time', 'name' => 'Dedicación exclusiva', 'sort_order' => 1],
            ['code' => 'half_time', 'name' => 'Medio tiempo', 'sort_order' => 2],
            ['code' => 'per_subject', 'name' => 'Por asignatura', 'sort_order' => 3],
        ];

        foreach ($types as $type) {
            DedicationType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedEmploymentStatuses(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $statuses
         */
        $statuses = [
            ['code' => 'active', 'name' => 'Activo', 'sort_order' => 1],
            ['code' => 'on_leave', 'name' => 'En licencia', 'sort_order' => 2],
            ['code' => 'retired', 'name' => 'Jubilado', 'sort_order' => 3],
            ['code' => 'suspended', 'name' => 'Suspendido', 'sort_order' => 4],
        ];

        foreach ($statuses as $status) {
            EmploymentStatus::firstOrCreate(
                ['code' => $status['code']],
                ['name' => $status['name'], 'active' => true, 'sort_order' => $status['sort_order']],
            );
        }
    }
}
