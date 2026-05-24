<?php

namespace Database\Seeders\Demo;

use App\Enums\EducationalLevel;
use App\Models\Career;
use App\Models\Guardian;
use App\Models\Pensum;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
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
                        'name' => fake()->name(),
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

    private function seedSecondaryStudentsWithGuardians(Role $estudianteRole, Role $representanteRole): void
    {
        // 20 secondary students paired with 20 guardians (rep01–rep20, sec01–sec20)
        for ($i = 1; $i <= 20; $i++) {
            $num = sprintf('%02d', $i);

            // Create guardian user first
            $repEmail = "rep{$num}@utcacao.edu.ve";
            $repUser = User::firstOrCreate(
                ['email' => $repEmail],
                [
                    'name' => fake()->name(),
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $repUser->syncRoles([$representanteRole->name]);

            $guardian = Guardian::firstOrCreate(['user_id' => $repUser->id]);

            // academic_year: 4 students per year (years 1–5)
            $academicYear = (int) ceil($i / 4);

            $secEmail = "sec{$num}@utcacao.edu.ve";
            $secUser = User::firstOrCreate(
                ['email' => $secEmail],
                [
                    'name' => fake()->name(),
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $secUser->syncRoles([$estudianteRole->name]);

            $student = Student::firstOrCreate(
                ['user_id' => $secUser->id],
                [
                    'educational_level' => EducationalLevel::Secondary,
                    'current_pensum_id' => null,
                    'academic_year' => $academicYear,
                ],
            );

            // Attach guardian via pivot (only when first created)
            if ($student->wasRecentlyCreated) {
                $student->guardians()->attach($guardian->id, [
                    'kinship_type_id' => DB::table('kinship_types')->where('code', 'mother')->value('id'),
                    'is_primary' => true,
                    'is_emergency_contact' => true,
                ]);
            }
        }
    }
}
