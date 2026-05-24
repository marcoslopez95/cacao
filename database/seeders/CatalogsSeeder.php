<?php

namespace Database\Seeders;

use Database\Seeders\Catalogs\AcademicCatalogsSeeder;
use Database\Seeders\Catalogs\GeographicSeeder;
use Database\Seeders\Catalogs\SocialCatalogsSeeder;
use Database\Seeders\Catalogs\SocioeconomicCatalogsSeeder;
use Database\Seeders\Catalogs\StaffCatalogsSeeder;
use Database\Seeders\Catalogs\UserProfileCatalogsSeeder;
use Illuminate\Database\Seeder;

class CatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GeographicSeeder::class,
            UserProfileCatalogsSeeder::class,
            AcademicCatalogsSeeder::class,
            StaffCatalogsSeeder::class,
            SocialCatalogsSeeder::class,
            SocioeconomicCatalogsSeeder::class,
        ]);
    }
}
