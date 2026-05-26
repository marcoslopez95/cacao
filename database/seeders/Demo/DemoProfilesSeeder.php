<?php

namespace Database\Seeders\Demo;

use App\Models\Catalogs\AttachmentDocumentType;
use App\Models\Catalogs\BasicService;
use App\Models\Catalogs\BloodType;
use App\Models\Catalogs\CommuteTime;
use App\Models\Catalogs\ConstructionMaterial;
use App\Models\Catalogs\ContractType;
use App\Models\Catalogs\DedicationType;
use App\Models\Catalogs\DigitalLevel;
use App\Models\Catalogs\DisabilityType;
use App\Models\Catalogs\DocumentType;
use App\Models\Catalogs\EducationLevel;
use App\Models\Catalogs\EmploymentStatus;
use App\Models\Catalogs\EmploymentType;
use App\Models\Catalogs\Gender;
use App\Models\Catalogs\GeographicZone;
use App\Models\Catalogs\HouseholdHeadType;
use App\Models\Catalogs\HousingType;
use App\Models\Catalogs\IncomeRange;
use App\Models\Catalogs\IncomeSource;
use App\Models\Catalogs\InstitutionalBenefit;
use App\Models\Catalogs\InstitutionType;
use App\Models\Catalogs\InsuranceType;
use App\Models\Catalogs\Language;
use App\Models\Catalogs\LanguageLevel;
use App\Models\Catalogs\LivingArrangement;
use App\Models\Catalogs\MaritalStatus;
use App\Models\Catalogs\Religion;
use App\Models\Catalogs\TenureType;
use App\Models\Catalogs\TransferReason;
use App\Models\Catalogs\TransportType;
use App\Models\Country;
use App\Models\DemographicProfile;
use App\Models\FamilyProfile;
use App\Models\Guardian;
use App\Models\GuardianProfile;
use App\Models\HealthProfile;
use App\Models\HousingProfile;
use App\Models\Municipality;
use App\Models\Parish;
use App\Models\Professor;
use App\Models\SocioeconomicProfile;
use App\Models\StaffProfile;
use App\Models\State;
use App\Models\Student;
use App\Models\StudentBackground;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemoProfilesSeeder extends Seeder
{
    /** @var array<string, array<string, int>> */
    private array $catalogs = [];

    private int $venezuelaId;

    /** @var array<int, array{state_id: int, municipality_id: int|null, parish_id: int|null}> */
    private array $geoCombos = [];

    private int $adminId;

    private const FIRST_NAMES = [
        'Carlos', 'Ana', 'Luis', 'María', 'Pedro', 'Carmen', 'José', 'Elena',
        'Roberto', 'Patricia', 'Miguel', 'Laura', 'Andrés', 'Sofía', 'Fernando',
        'Gabriela', 'Alejandro', 'Valentina', 'Ricardo', 'Daniela', 'Jorge',
        'Isabella', 'Oscar', 'Camila', 'Héctor', 'Mariana', 'Rafael', 'Paula',
        'Eduardo', 'Natalia', 'Ángel', 'Luisa', 'Manuel', 'Carolina', 'Jesús',
        'Adriana', 'Antonio', 'Beatriz', 'Francisco', 'Verónica',
    ];

    private const LAST_NAMES = [
        'García', 'Rodríguez', 'González', 'Martínez', 'López', 'Pérez',
        'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Mendoza', 'Vargas',
        'Díaz', 'Morales', 'Herrera', 'Jiménez', 'Gutiérrez', 'Ramos',
        'Castro', 'Álvarez', 'Romero', 'Suárez', 'Medina', 'Reyes',
        'Briceño', 'Salazar', 'Montoya', 'Silva', 'Ortega', 'Blanco',
    ];

    private const BARRIOS = [
        'Urb. Las Mercedes', 'Urb. Los Palos Grandes', 'Urb. Altamira',
        'Urb. La California', 'Urb. El Paraíso', 'Sector Bello Monte',
        'Urb. Los Chaguaramos', 'Urb. Santa Fe', 'Sector La Pastora',
        'Urb. Chuao', 'Sector Petare Norte', 'Urb. El Llanito',
        'Res. La Boyera', 'Urb. Colinas de Bello Monte', 'Sector Los Magallanes',
    ];

    private const OCCUPATIONS = [
        'Docente', 'Enfermero/a', 'Comerciante', 'Ingeniero/a',
        'Contador/a', 'Abogado/a', 'Médico/a', 'Mecánico/a',
        'Secretario/a', 'Administrador/a', 'Técnico/a de informática',
        'Obrero/a', 'Vendedor/a', 'Supervisor/a', 'Electricista',
    ];

    private const ACADEMIC_TITLES = [
        'Lic. en Informática', 'Ing. de Sistemas', 'Ing. Civil',
        'Lic. en Matemáticas', 'Mg. en Computación', 'Dr. en Ciencias',
        'Lic. en Contaduría', 'Ing. Eléctrico', 'Lic. en Educación',
        'Mg. en Administración', 'Dr. en Ingeniería', 'Lic. en Física',
    ];

    private const SPECIALTIES = [
        'Bases de datos', 'Redes y telecomunicaciones', 'Ingeniería de software',
        'Cálculo y álgebra lineal', 'Estructura de datos', 'Sistemas operativos',
        'Contabilidad de costos', 'Circuitos eléctricos', 'Pedagogía universitaria',
        'Gestión organizacional', 'Mecánica de suelos', 'Estadística aplicada',
    ];

    public function run(): void
    {
        $this->loadCatalogs();

        $students = Student::with('user')->get();
        $guardians = Guardian::with('user')->get();
        $professors = Professor::with('user')->get();

        $allUsers = $students->pluck('user')
            ->merge($guardians->pluck('user'))
            ->merge($professors->pluck('user'))
            ->filter()
            ->unique('id')
            ->values();

        $this->seedUserBaseData($allUsers);
        $this->seedDemographicProfiles($allUsers);
        $this->seedHealthProfiles($allUsers);

        $this->seedStudentProfiles($students);
        $this->seedGuardianProfiles($guardians);
        $this->seedStaffProfiles($professors);
    }

    private function loadCatalogs(): void
    {
        $this->venezuelaId = Country::where('iso2', 'VE')->value('id');
        $this->adminId = User::where('email', 'admin@cacao.edu.ve')->value('id');

        // Build geographic combos: state + municipality + optional parish
        $stateIds = State::where('country_id', $this->venezuelaId)->pluck('id')->take(8)->all();
        foreach ($stateIds as $stateId) {
            $muniIds = Municipality::where('state_id', $stateId)->pluck('id')->take(3)->all();
            foreach ($muniIds as $muniId) {
                $parishId = Parish::where('municipality_id', $muniId)->value('id');
                $this->geoCombos[] = [
                    'state_id' => $stateId,
                    'municipality_id' => $muniId,
                    'parish_id' => $parishId,
                ];
            }
        }

        // Fallback if no geographic data yet
        if (empty($this->geoCombos)) {
            $this->geoCombos[] = [
                'state_id' => $stateIds[0] ?? null,
                'municipality_id' => null,
                'parish_id' => null,
            ];
        }

        $this->catalogs['gender'] = Gender::pluck('id', 'code')->all();
        $this->catalogs['document_type'] = DocumentType::pluck('id', 'code')->all();
        $this->catalogs['geo_zone'] = GeographicZone::pluck('id', 'code')->all();
        $this->catalogs['attachment_type'] = AttachmentDocumentType::pluck('id', 'code')->all();
        $this->catalogs['blood_type'] = BloodType::pluck('id', 'code')->all();
        $this->catalogs['disability_type'] = DisabilityType::pluck('id', 'code')->all();
        $this->catalogs['insurance_type'] = InsuranceType::pluck('id', 'code')->all();
        $this->catalogs['education_level'] = EducationLevel::pluck('id', 'code')->all();
        $this->catalogs['institution_type'] = InstitutionType::pluck('id', 'code')->all();
        $this->catalogs['transfer_reason'] = TransferReason::pluck('id', 'code')->all();
        $this->catalogs['digital_level'] = DigitalLevel::pluck('id', 'code')->all();
        $this->catalogs['language'] = Language::pluck('id', 'code')->all();
        $this->catalogs['language_level'] = LanguageLevel::pluck('id', 'code')->all();
        $this->catalogs['marital_status'] = MaritalStatus::pluck('id', 'code')->all();
        $this->catalogs['living_arrangement'] = LivingArrangement::pluck('id', 'code')->all();
        $this->catalogs['household_head_type'] = HouseholdHeadType::pluck('id', 'code')->all();
        $this->catalogs['religion'] = Religion::pluck('id', 'code')->all();
        $this->catalogs['income_range'] = IncomeRange::pluck('id', 'code')->all();
        $this->catalogs['income_source'] = IncomeSource::pluck('id', 'code')->all();
        $this->catalogs['employment_type'] = EmploymentType::pluck('id', 'code')->all();
        $this->catalogs['institutional_benefit'] = InstitutionalBenefit::pluck('id', 'code')->all();
        $this->catalogs['housing_type'] = HousingType::pluck('id', 'code')->all();
        $this->catalogs['tenure_type'] = TenureType::pluck('id', 'code')->all();
        $this->catalogs['construction_material'] = ConstructionMaterial::pluck('id', 'code')->all();
        $this->catalogs['commute_time'] = CommuteTime::pluck('id', 'code')->all();
        $this->catalogs['transport_type'] = TransportType::pluck('id', 'code')->all();
        $this->catalogs['basic_service'] = BasicService::pluck('id', 'code')->all();
        $this->catalogs['contract_type'] = ContractType::pluck('id', 'code')->all();
        $this->catalogs['dedication_type'] = DedicationType::pluck('id', 'code')->all();
        $this->catalogs['employment_status'] = EmploymentStatus::pluck('id', 'code')->all();
    }

    private function id(string $catalog, string $code): int
    {
        return $this->catalogs[$catalog][$code];
    }

    private function pick(string $catalog, array $codes): int
    {
        return $this->catalogs[$catalog][fake()->randomElement($codes)];
    }

    private function geo(): array
    {
        return fake()->randomElement($this->geoCombos);
    }

    /**
     * Update users with first_name/last_name and create UserAddress + UserDocument.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedUserBaseData(Collection $users): void
    {
        $documentTypeId = $this->id('document_type', 'V');
        $idCardTypeId = $this->id('attachment_type', 'id_card');
        $documentNumber = 8000000;

        foreach ($users as $user) {
            // Update name fields if missing (generated column requires first_name/last_name)
            if (empty($user->first_name)) {
                $user->update([
                    'first_name' => fake()->randomElement(self::FIRST_NAMES),
                    'last_name' => fake()->randomElement(self::LAST_NAMES).' '.fake()->randomElement(self::LAST_NAMES),
                    'document_type_id' => $documentTypeId,
                    'document_number' => (string) $documentNumber,
                    'birth_date' => fake()->dateTimeBetween('-45 years', '-17 years')->format('Y-m-d'),
                    'gender_id' => $this->pick('gender', ['male', 'female']),
                    'nationality_id' => $this->venezuelaId,
                    'phone_primary' => '0414-'.fake()->numerify('#######'),
                ]);
            }
            $documentNumber++;

            // UserAddress (primary, one per user)
            if (! $user->addresses()->exists()) {
                $geo = $this->geo();
                UserAddress::create([
                    'user_id' => $user->id,
                    'country_id' => $this->venezuelaId,
                    'state_id' => $geo['state_id'],
                    'municipality_id' => $geo['municipality_id'],
                    'parish_id' => $geo['parish_id'],
                    'geographic_zone_id' => $this->pick('geo_zone', ['urban', 'periurban', 'rural']),
                    'address_line1' => 'Calle '.fake()->numberBetween(1, 80).', '.fake()->randomElement(self::BARRIOS),
                    'is_primary' => true,
                    'created_at' => now(),
                ]);
            }

            // UserDocument: cédula de identidad
            if (! $user->documents()->where('attachment_type_id', $idCardTypeId)->exists()) {
                UserDocument::create([
                    'user_id' => $user->id,
                    'attachment_type_id' => $idCardTypeId,
                    'file_url' => '/demo/documents/cedula_placeholder.pdf',
                    'original_filename' => 'cedula_identidad.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => 204800,
                    'is_verified' => true,
                    'verified_by' => $this->adminId,
                    'verified_at' => now()->subDays(fake()->numberBetween(5, 60)),
                    'created_at' => now(),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedDemographicProfiles(Collection $users): void
    {
        foreach ($users as $user) {
            if ($user->demographicProfile()->exists()) {
                continue;
            }

            $isPracticingSport = fake()->boolean(40);
            $isIndigenous = fake()->boolean(5);
            $isReturnedMigrant = fake()->boolean(10);

            DemographicProfile::create([
                'user_id' => $user->id,
                'birth_city' => fake()->randomElement(['Caracas', 'Maracaibo', 'Valencia', 'Barquisimeto', 'Maturín', 'Barcelona', 'Maracay', 'Ciudad Bolívar', 'Cumaná', 'Mérida']),
                'birth_state_id' => fake()->randomElement(array_column($this->geoCombos, 'state_id')),
                'birth_country_id' => $this->venezuelaId,
                'is_indigenous' => $isIndigenous,
                'indigenous_community' => $isIndigenous ? fake()->randomElement(['Wayuu', 'Pemon', 'Yanomami', 'Añú', 'Kariña']) : null,
                'native_language_id' => $this->id('language', 'es'),
                'is_returned_migrant' => $isReturnedMigrant,
                'previous_country_id' => $isReturnedMigrant ? Country::where('iso2', fake()->randomElement(['CO', 'PE', 'CL', 'AR', 'EC']))->value('id') : null,
                'religion_id' => $this->pick('religion', ['catholic', 'evangelical', 'agnostic', 'atheist', 'protestant']),
                'practices_sport' => $isPracticingSport,
                'sport' => $isPracticingSport ? fake()->randomElement(['Fútbol', 'Béisbol', 'Baloncesto', 'Natación', 'Atletismo', 'Softbol', 'Voleibol']) : null,
                'cultural_activities' => fake()->boolean(30) ? fake()->randomElement(['Música', 'Teatro', 'Danza', 'Artes plásticas', 'Fotografía', 'Literatura']) : null,
            ]);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedHealthProfiles(Collection $users): void
    {
        foreach ($users as $user) {
            if ($user->healthProfile()->exists()) {
                continue;
            }

            $hasDisability = fake()->boolean(5);
            $hasMedicalInsurance = fake()->boolean(30);

            HealthProfile::create([
                'user_id' => $user->id,
                'blood_type_id' => $this->pick('blood_type', ['o_pos', 'a_pos', 'b_pos', 'ab_pos', 'o_neg', 'a_neg']),
                'weight_kg' => fake()->randomFloat(1, 48, 95),
                'height_cm' => fake()->randomFloat(1, 152, 190),
                'has_disability' => $hasDisability,
                'disability_type_id' => $hasDisability ? $this->pick('disability_type', ['visual', 'hearing', 'motor', 'cognitive']) : null,
                'disability_description' => $hasDisability ? fake()->sentence(6) : null,
                'has_special_needs' => fake()->boolean(8),
                'special_needs_description' => null,
                'chronic_condition' => fake()->boolean(10) ? fake()->randomElement(['Diabetes tipo 2', 'Hipertensión', 'Asma', 'Anemia']) : null,
                'regular_medication' => null,
                'allergies' => fake()->boolean(15) ? fake()->randomElement(['Penicilina', 'Polen', 'Mariscos', 'Polvo', 'Látex']) : null,
                'has_medical_insurance' => $hasMedicalInsurance,
                'insurance_type_id' => $hasMedicalInsurance ? $this->pick('insurance_type', ['ivss', 'private_hcm']) : $this->id('insurance_type', 'none'),
                'emergency_contact_name' => fake()->randomElement(self::FIRST_NAMES).' '.fake()->randomElement(self::LAST_NAMES),
                'emergency_contact_phone' => '0412-'.fake()->numerify('#######'),
                'emergency_contact_relation' => fake()->randomElement(['Madre', 'Padre', 'Hermano/a', 'Cónyuge', 'Familiar']),
            ]);
        }
    }

    /**
     * @param  Collection<int, Student>  $students
     */
    private function seedStudentProfiles(Collection $students): void
    {
        $spanishId = $this->id('language', 'es');
        $englishId = $this->id('language', 'en');
        $nativeLevelId = $this->id('language_level', 'native');
        $cafeteriaId = $this->id('institutional_benefit', 'cafeteria');
        $transportBenefitId = $this->id('institutional_benefit', 'transport');
        $waterServiceId = $this->id('basic_service', 'potable_water');
        $electricityServiceId = $this->id('basic_service', 'electricity');
        $internetServiceId = $this->id('basic_service', 'internet');

        foreach ($students as $student) {
            // StudentBackground
            if (! $student->background()->exists()) {
                $repeatedGrade = fake()->boolean(10);
                $hasPriorStudies = fake()->boolean(15);

                StudentBackground::create([
                    'student_id' => $student->id,
                    'previous_institution' => fake()->randomElement([
                        'U.E. Colegio San Agustín', 'U.E. La Salle', 'Liceo Gustavo Herrera',
                        'U.E. Don Bosco', 'Liceo Andrés Bello', 'U.E. Santa Rosa de Lima',
                        'Liceo Simón Bolívar', 'U.E. San José de Tarbes', 'Liceo Luis Razetti',
                        'U.E. Nuestra Señora del Carmen',
                    ]),
                    'institution_type_id' => $this->pick('institution_type', ['public', 'private', 'fe_y_alegria']),
                    'graduation_year' => fake()->numberBetween(2018, 2024),
                    'previous_gpa' => fake()->randomFloat(2, 12, 20),
                    'repeated_grade' => $repeatedGrade,
                    'repeated_grade_description' => $repeatedGrade ? fake()->sentence(5) : null,
                    'transfer_reason_id' => fake()->boolean(20) ? $this->pick('transfer_reason', ['relocation', 'economic']) : null,
                    'has_prior_studies' => $hasPriorStudies,
                    'prior_studies_description' => $hasPriorStudies ? fake()->sentence(8) : null,
                    'digital_level_id' => $this->pick('digital_level', ['basic', 'intermediate', 'intermediate', 'advanced']),
                    'mother_education_level_id' => $this->pick('education_level', ['primary', 'secondary', 'undergraduate']),
                    'father_education_level_id' => $this->pick('education_level', ['primary', 'secondary', 'undergraduate', 'technical_tsu']),
                ]);
            }

            // FamilyProfile
            if (! $student->familyProfile()->exists()) {
                $siblingCount = fake()->numberBetween(0, 5);

                FamilyProfile::create([
                    'student_id' => $student->id,
                    'guardian_marital_status_id' => $this->pick('marital_status', ['married', 'divorced', 'civil_union', 'widowed', 'single']),
                    'children_count' => fake()->numberBetween(0, 3),
                    'sibling_count' => $siblingCount,
                    'sibling_position' => $siblingCount > 0 ? fake()->numberBetween(1, $siblingCount) : 1,
                    'living_arrangement_id' => $this->pick('living_arrangement', ['both_parents', 'both_parents', 'mother_only', 'father_only', 'relative', 'independent']),
                    'household_head_type_id' => $this->pick('household_head_type', ['father', 'mother', 'mother', 'other_relative']),
                    'household_head_name' => fake()->randomElement(self::FIRST_NAMES).' '.fake()->randomElement(self::LAST_NAMES),
                ]);
            }

            // SocioeconomicProfile
            if (! $student->socioeconomicProfile()->exists()) {
                $studentWorks = fake()->boolean(30);
                $receivesRemittances = fake()->boolean(25);
                $hasScholarship = fake()->boolean(15);

                SocioeconomicProfile::create([
                    'student_id' => $student->id,
                    'income_range_id' => $this->pick('income_range', ['under_50_usd', '50_to_150_usd', '50_to_150_usd', '150_to_300_usd', '300_to_500_usd']),
                    'income_source_id' => $this->pick('income_source', ['formal_employment', 'informal_employment', 'own_business', 'remittances']),
                    'household_earners' => fake()->numberBetween(1, 4),
                    'receives_remittances' => $receivesRemittances,
                    'remittance_country_id' => $receivesRemittances ? Country::where('iso2', fake()->randomElement(['CO', 'PE', 'CL', 'AR', 'US', 'ES']))->value('id') : null,
                    'student_works' => $studentWorks,
                    'employment_type_id' => $studentWorks ? $this->pick('employment_type', ['informal', 'freelance', 'family_business']) : null,
                    'weekly_work_hours' => $studentWorks ? fake()->numberBetween(8, 24) : null,
                    'has_scholarship' => $hasScholarship,
                    'scholarship_name' => $hasScholarship ? fake()->randomElement(['OPSU', 'Fundayacucho', 'Beca Institucional', 'AID']) : null,
                    'has_institutional_benefit' => fake()->boolean(25),
                    'recorded_by' => $this->adminId,
                    'study_date' => now()->subDays(fake()->numberBetween(1, 90))->toDateString(),
                ]);
            }

            // HousingProfile + services
            if (! $student->housingProfile()->exists()) {
                $housing = HousingProfile::create([
                    'student_id' => $student->id,
                    'housing_type_id' => $this->pick('housing_type', ['house', 'house', 'apartment', 'rented_room', 'quinta']),
                    'tenure_type_id' => $this->pick('tenure_type', ['owned', 'owned', 'rented', 'borrowed']),
                    'construction_material_id' => $this->pick('construction_material', ['reinforced_concrete', 'reinforced_concrete', 'mixed', 'wood']),
                    'room_count' => fake()->numberBetween(2, 5),
                    'bathroom_count' => fake()->numberBetween(1, 3),
                    'household_members' => fake()->numberBetween(2, 7),
                    'commute_time_id' => $this->pick('commute_time', ['under_15min', '15_to_30min', '30_to_60min', 'over_1hour']),
                    'transport_type_id' => $this->pick('transport_type', ['public_transport', 'public_transport', 'own_vehicle', 'motorcycle', 'on_foot']),
                ]);

                // Basic services — water + electricity always, rest random
                $services = [
                    $waterServiceId => ['is_available' => true],
                    $electricityServiceId => ['is_available' => true],
                ];
                if (fake()->boolean(60)) {
                    $services[$internetServiceId] = ['is_available' => true];
                }
                if (fake()->boolean(70)) {
                    $services[$this->id('basic_service', 'sewer')] = ['is_available' => true];
                }
                if (fake()->boolean(50)) {
                    $services[$this->id('basic_service', 'garbage_collection')] = ['is_available' => true];
                }
                $housing->services()->syncWithoutDetaching($services);
            }

            // StudentLanguages: Spanish native always, 50% also English
            $hasSpanish = DB::table('student_languages')
                ->where('student_id', $student->id)
                ->where('language_id', $spanishId)
                ->exists();

            if (! $hasSpanish) {
                DB::table('student_languages')->insertOrIgnore([
                    'student_id' => $student->id,
                    'language_id' => $spanishId,
                    'language_level_id' => $nativeLevelId,
                    'is_mother_tongue' => true,
                ]);
            }

            if (fake()->boolean(50)) {
                $hasEnglish = DB::table('student_languages')
                    ->where('student_id', $student->id)
                    ->where('language_id', $englishId)
                    ->exists();

                if (! $hasEnglish) {
                    DB::table('student_languages')->insertOrIgnore([
                        'student_id' => $student->id,
                        'language_id' => $englishId,
                        'language_level_id' => $this->pick('language_level', ['a1', 'a2', 'b1', 'b2']),
                        'is_mother_tongue' => false,
                    ]);
                }
            }

            // StudentBenefits: 30% cafeteria, 20% transport
            if (fake()->boolean(30)) {
                DB::table('student_benefits')->insertOrIgnore([
                    'student_id' => $student->id,
                    'benefit_id' => $cafeteriaId,
                    'is_active' => true,
                    'since' => '2025-09-01',
                    'until' => '2025-12-31',
                ]);
            }
            if (fake()->boolean(20)) {
                DB::table('student_benefits')->insertOrIgnore([
                    'student_id' => $student->id,
                    'benefit_id' => $transportBenefitId,
                    'is_active' => true,
                    'since' => '2025-09-01',
                    'until' => '2025-12-31',
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Guardian>  $guardians
     */
    private function seedGuardianProfiles(Collection $guardians): void
    {
        foreach ($guardians as $guardian) {
            if ($guardian->profile()->exists()) {
                continue;
            }

            GuardianProfile::create([
                'guardian_id' => $guardian->id,
                'occupation' => fake()->randomElement(self::OCCUPATIONS),
                'employer' => fake()->randomElement([
                    'PDVSA', 'Ministerio de Educación', 'Clínica Privada', 'Empresa propia',
                    'Alcaldía', 'Banco Mercantil', 'Empresa familiar', 'Freelance',
                    'CANTV', 'CORPOELEC', 'Gobernación',
                ]),
                'work_phone' => '0212-'.fake()->numerify('#######'),
                'education_level_id' => $this->pick('education_level', ['secondary', 'technical_tsu', 'undergraduate', 'postgraduate']),
                'marital_status_id' => $this->pick('marital_status', ['married', 'married', 'divorced', 'civil_union', 'widowed']),
            ]);
        }
    }

    /**
     * @param  Collection<int, Professor>  $professors
     */
    private function seedStaffProfiles(Collection $professors): void
    {
        $idx = 1;

        foreach ($professors as $professor) {
            if ($professor->staffProfile()->exists()) {
                continue;
            }

            StaffProfile::create([
                'professor_id' => $professor->id,
                'employee_code' => sprintf('PROF-%04d', $idx),
                'academic_title' => fake()->randomElement(self::ACADEMIC_TITLES),
                'specialty' => fake()->randomElement(self::SPECIALTIES),
                'contract_type_id' => $this->pick('contract_type', ['permanent', 'permanent', 'contracted', 'hourly']),
                'dedication_type_id' => $this->pick('dedication_type', ['full_time', 'half_time', 'per_subject']),
                'weekly_hour_load' => fake()->randomElement([12, 16, 20, 24]),
                'hire_date' => fake()->dateTimeBetween('-15 years', '-2 years')->format('Y-m-d'),
                'termination_date' => null,
                'employment_status_id' => $this->id('employment_status', 'active'),
                'is_coordinator' => false,
                'coordinated_department_id' => null,
                'coordinator_since' => null,
            ]);

            $idx++;
        }
    }
}
