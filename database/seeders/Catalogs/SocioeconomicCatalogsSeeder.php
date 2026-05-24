<?php

namespace Database\Seeders\Catalogs;

use App\Models\Catalogs\BasicService;
use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\CommuteTime;
use App\Models\Catalogs\ConstructionMaterial;
use App\Models\Catalogs\DisabilityType;
use App\Models\Catalogs\EmploymentType;
use App\Models\Catalogs\HousingType;
use App\Models\Catalogs\IncomeRange;
use App\Models\Catalogs\IncomeSource;
use App\Models\Catalogs\InstitutionalBenefit;
use App\Models\Catalogs\InsuranceType;
use App\Models\Catalogs\TenureType;
use App\Models\Catalogs\TransportType;
use Illuminate\Database\Seeder;

class SocioeconomicCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedIncomeRanges();
        $this->seedIncomeSources();
        $this->seedEmploymentTypes();
        $this->seedInstitutionalBenefits();
        $this->seedHousingTypes();
        $this->seedTenureTypes();
        $this->seedConstructionMaterials();
        $this->seedBasicServices();
        $this->seedCommuteTimes();
        $this->seedTransportTypes();
        $this->seedDisabilityTypes();
        $this->seedInsuranceTypes();
        $this->seedBloodTypes();
    }

    private function seedIncomeRanges(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $ranges
         */
        $ranges = [
            ['code' => 'under_50_usd', 'name' => 'Menos de $50', 'sort_order' => 1],
            ['code' => '50_to_150_usd', 'name' => '$50 – $150', 'sort_order' => 2],
            ['code' => '150_to_300_usd', 'name' => '$150 – $300', 'sort_order' => 3],
            ['code' => '300_to_500_usd', 'name' => '$300 – $500', 'sort_order' => 4],
            ['code' => 'over_500_usd', 'name' => 'Más de $500', 'sort_order' => 5],
        ];

        foreach ($ranges as $range) {
            IncomeRange::firstOrCreate(
                ['code' => $range['code']],
                ['name' => $range['name'], 'active' => true, 'sort_order' => $range['sort_order']],
            );
        }
    }

    private function seedIncomeSources(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $sources
         */
        $sources = [
            ['code' => 'formal_employment', 'name' => 'Empleo formal', 'sort_order' => 1],
            ['code' => 'informal_employment', 'name' => 'Empleo informal', 'sort_order' => 2],
            ['code' => 'own_business', 'name' => 'Negocio propio', 'sort_order' => 3],
            ['code' => 'remittances', 'name' => 'Remesas', 'sort_order' => 4],
            ['code' => 'pension', 'name' => 'Pensión o jubilación', 'sort_order' => 5],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 6],
        ];

        foreach ($sources as $source) {
            IncomeSource::firstOrCreate(
                ['code' => $source['code']],
                ['name' => $source['name'], 'active' => true, 'sort_order' => $source['sort_order']],
            );
        }
    }

    private function seedEmploymentTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'formal', 'name' => 'Formal', 'sort_order' => 1],
            ['code' => 'informal', 'name' => 'Informal', 'sort_order' => 2],
            ['code' => 'freelance', 'name' => 'Independiente/freelance', 'sort_order' => 3],
            ['code' => 'family_business', 'name' => 'Negocio familiar', 'sort_order' => 4],
        ];

        foreach ($types as $type) {
            EmploymentType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedInstitutionalBenefits(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $benefits
         */
        $benefits = [
            ['code' => 'cafeteria', 'name' => 'Comedor', 'sort_order' => 1],
            ['code' => 'transport', 'name' => 'Transporte', 'sort_order' => 2],
            ['code' => 'supplies', 'name' => 'Útiles y materiales', 'sort_order' => 3],
            ['code' => 'partial_scholarship', 'name' => 'Beca parcial', 'sort_order' => 4],
            ['code' => 'full_scholarship', 'name' => 'Beca completa', 'sort_order' => 5],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 6],
        ];

        foreach ($benefits as $benefit) {
            InstitutionalBenefit::firstOrCreate(
                ['code' => $benefit['code']],
                ['name' => $benefit['name'], 'active' => true, 'sort_order' => $benefit['sort_order']],
            );
        }
    }

    private function seedHousingTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'house', 'name' => 'Casa', 'sort_order' => 1],
            ['code' => 'apartment', 'name' => 'Apartamento', 'sort_order' => 2],
            ['code' => 'rented_room', 'name' => 'Habitación alquilada', 'sort_order' => 3],
            ['code' => 'rancho', 'name' => 'Rancho', 'sort_order' => 4],
            ['code' => 'quinta', 'name' => 'Quinta', 'sort_order' => 5],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 6],
        ];

        foreach ($types as $type) {
            HousingType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedTenureTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'owned', 'name' => 'Propia', 'sort_order' => 1],
            ['code' => 'rented', 'name' => 'Alquilada', 'sort_order' => 2],
            ['code' => 'borrowed', 'name' => 'Cedida/prestada', 'sort_order' => 3],
            ['code' => 'mortgaged', 'name' => 'Hipotecada', 'sort_order' => 4],
        ];

        foreach ($types as $type) {
            TenureType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedConstructionMaterials(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $materials
         */
        $materials = [
            ['code' => 'reinforced_concrete', 'name' => 'Concreto/bloque', 'sort_order' => 1],
            ['code' => 'wood', 'name' => 'Madera', 'sort_order' => 2],
            ['code' => 'zinc', 'name' => 'Zinc', 'sort_order' => 3],
            ['code' => 'mixed', 'name' => 'Mixto', 'sort_order' => 4],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 5],
        ];

        foreach ($materials as $material) {
            ConstructionMaterial::firstOrCreate(
                ['code' => $material['code']],
                ['name' => $material['name'], 'active' => true, 'sort_order' => $material['sort_order']],
            );
        }
    }

    private function seedBasicServices(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $services
         */
        $services = [
            ['code' => 'potable_water', 'name' => 'Agua potable', 'sort_order' => 1],
            ['code' => 'electricity', 'name' => 'Electricidad', 'sort_order' => 2],
            ['code' => 'gas', 'name' => 'Gas', 'sort_order' => 3],
            ['code' => 'internet', 'name' => 'Internet', 'sort_order' => 4],
            ['code' => 'sewer', 'name' => 'Cloacas', 'sort_order' => 5],
            ['code' => 'garbage_collection', 'name' => 'Recolección de basura', 'sort_order' => 6],
            ['code' => 'landline', 'name' => 'Teléfono fijo', 'sort_order' => 7],
        ];

        foreach ($services as $service) {
            BasicService::firstOrCreate(
                ['code' => $service['code']],
                ['name' => $service['name'], 'active' => true, 'sort_order' => $service['sort_order']],
            );
        }
    }

    private function seedCommuteTimes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $times
         */
        $times = [
            ['code' => 'under_15min', 'name' => 'Menos de 15 min', 'sort_order' => 1],
            ['code' => '15_to_30min', 'name' => '15 – 30 min', 'sort_order' => 2],
            ['code' => '30_to_60min', 'name' => '30 – 60 min', 'sort_order' => 3],
            ['code' => 'over_1hour', 'name' => 'Más de 1 hora', 'sort_order' => 4],
        ];

        foreach ($times as $time) {
            CommuteTime::firstOrCreate(
                ['code' => $time['code']],
                ['name' => $time['name'], 'active' => true, 'sort_order' => $time['sort_order']],
            );
        }
    }

    private function seedTransportTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'own_vehicle', 'name' => 'Vehículo propio', 'sort_order' => 1],
            ['code' => 'public_transport', 'name' => 'Transporte público', 'sort_order' => 2],
            ['code' => 'on_foot', 'name' => 'A pie', 'sort_order' => 3],
            ['code' => 'motorcycle', 'name' => 'Moto', 'sort_order' => 4],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 5],
        ];

        foreach ($types as $type) {
            TransportType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedDisabilityTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'visual', 'name' => 'Visual', 'sort_order' => 1],
            ['code' => 'hearing', 'name' => 'Auditiva', 'sort_order' => 2],
            ['code' => 'motor', 'name' => 'Motora', 'sort_order' => 3],
            ['code' => 'cognitive', 'name' => 'Cognitiva', 'sort_order' => 4],
            ['code' => 'speech', 'name' => 'Del habla', 'sort_order' => 5],
            ['code' => 'multiple', 'name' => 'Múltiple', 'sort_order' => 6],
            ['code' => 'other', 'name' => 'Otra', 'sort_order' => 7],
        ];

        foreach ($types as $type) {
            DisabilityType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedInsuranceTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'ivss', 'name' => 'IVSS', 'sort_order' => 1],
            ['code' => 'private_hcm', 'name' => 'Póliza HCM privada', 'sort_order' => 2],
            ['code' => 'private_other', 'name' => 'Seguro privado (otro)', 'sort_order' => 3],
            ['code' => 'none', 'name' => 'Sin seguro', 'sort_order' => 4],
        ];

        foreach ($types as $type) {
            InsuranceType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }

    private function seedBloodTypes(): void
    {
        /**
         * @var array<int, array{code: string, name: string, sort_order: int}> $types
         */
        $types = [
            ['code' => 'a_pos', 'name' => 'A+', 'sort_order' => 1],
            ['code' => 'a_neg', 'name' => 'A−', 'sort_order' => 2],
            ['code' => 'b_pos', 'name' => 'B+', 'sort_order' => 3],
            ['code' => 'b_neg', 'name' => 'B−', 'sort_order' => 4],
            ['code' => 'ab_pos', 'name' => 'AB+', 'sort_order' => 5],
            ['code' => 'ab_neg', 'name' => 'AB−', 'sort_order' => 6],
            ['code' => 'o_pos', 'name' => 'O+', 'sort_order' => 7],
            ['code' => 'o_neg', 'name' => 'O−', 'sort_order' => 8],
        ];

        foreach ($types as $type) {
            BloodType::firstOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'active' => true, 'sort_order' => $type['sort_order']],
            );
        }
    }
}
