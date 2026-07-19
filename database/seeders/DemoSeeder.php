<?php

namespace Database\Seeders;

use Database\Seeders\Demo\DemoAcademicHistorySeeder;
use Database\Seeders\Demo\DemoAcademicSeeder;
use Database\Seeders\Demo\DemoEnrollmentSeeder;
use Database\Seeders\Demo\DemoInfrastructureSeeder;
use Database\Seeders\Demo\DemoPeriodSeeder;
use Database\Seeders\Demo\DemoProfessorsSeeder;
use Database\Seeders\Demo\DemoProfilesSeeder;
use Database\Seeders\Demo\DemoSectionsSeeder;
use Database\Seeders\Demo\DemoStudentsSeeder;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data.
     * Establishes complete academic workflow with realistic Venezuelan data.
     */
    public function run(): void
    {
        // Initialize roles, permissions, and base users
        $this->call([
            DatabaseSeeder::class,
            DemoAcademicSeeder::class,
            DemoInfrastructureSeeder::class,
            DemoPeriodSeeder::class,
            DemoProfessorsSeeder::class,
            DemoStudentsSeeder::class,
            DemoSectionsSeeder::class,
            DemoEnrollmentSeeder::class,
            DemoAcademicHistorySeeder::class,
            DemoProfilesSeeder::class,
        ]);
    }
}
