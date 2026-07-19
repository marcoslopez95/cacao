<?php

namespace Database\Seeders\Demo;

use App\Enums\EducationalLevel;
use App\Models\Career;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoStudentsSeeder extends Seeder
{
    /**
     * Career codes in order — 24 students each = 120 total.
     *
     * @var array<int, string>
     */
    private const CAREER_CODES = ['INF', 'CIV', 'CON', 'ADM', 'EDU'];

    private const SCHOOL_CAREER_CODE = 'BACH';

    private const SCHOOL_PENSUM_NAME = 'Pensum Bachillerato 2020';

    /**
     * Kinship type codes eligible for a secondary student's guardian link.
     *
     * @var array<int, string>
     */
    private const KINSHIP_CODES = ['father', 'mother', 'legal_guardian', 'grandparent', 'uncle', 'other'];

    /**
     * Fixed batch sizes (1-6 students per guardian) summing to 20 secondary students.
     * Fixed (not random) so re-seeding stays idempotent — a random count per run
     * would leave stray guardians with 0 students once a re-run draws a smaller batch count.
     *
     * @var array<int, int>
     */
    private const GUARDIAN_BATCH_SIZES = [6, 5, 4, 2, 2, 1];

    /**
     * Seed student accounts and guardian assignments.
     */
    public function run(): void
    {
        $estudianteRole = Role::where('name', 'Estudiante')->where('guard_name', 'web')->firstOrFail();
        $representanteRole = Role::where('name', 'Representante')->where('guard_name', 'web')->firstOrFail();

        $this->seedUniversityStudents($estudianteRole);
        $this->seedSecondaryStudentsWithGuardians($estudianteRole, $representanteRole);
    }

    private function seedUniversityStudents(Role $role): void
    {
        // 5 careers × 24 students = 120 university students
        foreach (self::CAREER_CODES as $careerIndex => $careerCode) {
            $career = Career::where('code', $careerCode)->firstOrFail();
            $pensum = Pensum::where('career_id', $career->id)
                ->where('is_active', true)
                ->firstOrFail();

            $startNum = $careerIndex * 24 + 1;

            for ($i = 0; $i < 24; $i++) {
                $num = $startNum + $i;
                $email = sprintf('est%03d@utcacao.edu.ve', $num);

                // academic_year cycles 1–4 (6 students per year within each career)
                $academicYear = (int) floor($i / 6) + 1;

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'first_name' => fake()->firstName(),
                        'last_name' => fake()->lastName().' '.fake()->lastName(),
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ],
                );

                $user->syncRoles([$role->name]);

                Student::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'educational_level' => EducationalLevel::University,
                        'current_pensum_id' => $pensum->id,
                        'academic_year' => $academicYear,
                    ],
                );
            }
        }
    }

    /**
     * Assigns the 20 secondary students to a pool of guardians. Guardians are
     * created in batches of random size (1-6 students each) instead of a
     * fixed 1:1 mapping, and the kinship type varies per link.
     */
    private function seedSecondaryStudentsWithGuardians(Role $estudianteRole, Role $representanteRole): void
    {
        $schoolCareer = Career::where('code', self::SCHOOL_CAREER_CODE)->firstOrFail();
        $schoolPensum = Pensum::where('career_id', $schoolCareer->id)
            ->where('name', self::SCHOOL_PENSUM_NAME)
            ->firstOrFail();

        /** @var Collection<string, int> $kinshipTypeIds */
        $kinshipTypeIds = DB::table('kinship_types')
            ->whereIn('code', self::KINSHIP_CODES)
            ->pluck('id', 'code');

        $batchSizes = self::GUARDIAN_BATCH_SIZES;

        $studentNum = 1;

        foreach ($batchSizes as $batchIndex => $batchSize) {
            $repNum = sprintf('%02d', $batchIndex + 1);
            $repEmail = "rep{$repNum}@utcacao.edu.ve";

            $repUser = User::firstOrCreate(
                ['email' => $repEmail],
                [
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName().' '.fake()->lastName(),
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $repUser->syncRoles([$representanteRole->name]);

            $guardian = Guardian::firstOrCreate(['user_id' => $repUser->id]);

            for ($i = 0; $i < $batchSize; $i++) {
                $num = sprintf('%02d', $studentNum);

                // academic_year: 4 students per year (years 1–5)
                $academicYear = (int) ceil($studentNum / 4);

                $secEmail = "sec{$num}@utcacao.edu.ve";
                $secUser = User::firstOrCreate(
                    ['email' => $secEmail],
                    [
                        'first_name' => fake()->firstName(),
                        'last_name' => fake()->lastName().' '.fake()->lastName(),
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                    ],
                );

                $secUser->syncRoles([$estudianteRole->name]);

                $student = Student::firstOrCreate(
                    ['user_id' => $secUser->id],
                    [
                        'educational_level' => EducationalLevel::Secondary,
                        'current_pensum_id' => $schoolPensum->id,
                        'academic_year' => $academicYear,
                    ],
                );

                // Backfill for students created before the school pensum existed.
                if ($student->current_pensum_id === null) {
                    $student->update(['current_pensum_id' => $schoolPensum->id]);
                }

                // Attach guardian via pivot (only when first created)
                if ($student->wasRecentlyCreated) {
                    $kinshipCode = fake()->randomElement(self::KINSHIP_CODES);

                    $student->guardians()->attach($guardian->id, [
                        'kinship_type_id' => $kinshipTypeIds[$kinshipCode],
                        'is_primary' => true,
                        'is_emergency_contact' => true,
                    ]);
                }

                $studentNum++;
            }
        }
    }
}
