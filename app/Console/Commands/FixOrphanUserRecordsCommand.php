<?php

namespace App\Console\Commands;

use App\Enums\EducationalLevel;
use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;

class FixOrphanUserRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-orphan-records
                            {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing sub-records for users with a role but no matching row in professors/students/guardians';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $fixedCount = 0;

        $fixedCount += $this->fixOrphanProfessors($isDryRun);
        $fixedCount += $this->fixOrphanStudents($isDryRun);
        $fixedCount += $this->fixOrphanGuardians($isDryRun);

        $prefix = $isDryRun ? '[dry-run] Would fix' : 'Fixed';
        $this->info("{$prefix} {$fixedCount} orphan record(s).");

        return Command::SUCCESS;
    }

    /**
     * Fix users with role Profesor but no row in professors.
     *
     * @return int Number of records fixed (or that would be fixed).
     */
    private function fixOrphanProfessors(bool $isDryRun): int
    {
        $fixedCount = 0;

        User::role('Profesor')
            ->whereDoesntHave('professor')
            ->each(function (User $user) use ($isDryRun, &$fixedCount): void {
                if (! $isDryRun) {
                    Professor::create(['user_id' => $user->id]);
                }
                $this->line("  Profesor: user #{$user->id} ({$user->name})");
                $fixedCount++;
            });

        return $fixedCount;
    }

    /**
     * Fix users with role Estudiante but no row in students.
     *
     * @return int Number of records fixed (or that would be fixed).
     */
    private function fixOrphanStudents(bool $isDryRun): int
    {
        $fixedCount = 0;

        User::role('Estudiante')
            ->whereDoesntHave('student')
            ->each(function (User $user) use ($isDryRun, &$fixedCount): void {
                if (! $isDryRun) {
                    Student::create([
                        'user_id' => $user->id,
                        'educational_level' => EducationalLevel::University,
                    ]);
                }
                $this->line("  Estudiante: user #{$user->id} ({$user->name})");
                $fixedCount++;
            });

        return $fixedCount;
    }

    /**
     * Fix users with role Representante but no row in guardians.
     *
     * @return int Number of records fixed (or that would be fixed).
     */
    private function fixOrphanGuardians(bool $isDryRun): int
    {
        $fixedCount = 0;

        User::role('Representante')
            ->whereDoesntHave('guardian')
            ->each(function (User $user) use ($isDryRun, &$fixedCount): void {
                if (! $isDryRun) {
                    Guardian::create(['user_id' => $user->id]);
                }
                $this->line("  Representante: user #{$user->id} ({$user->name})");
                $fixedCount++;
            });

        return $fixedCount;
    }
}
