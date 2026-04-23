<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Symfony\Component\Yaml\Yaml;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $data = Yaml::parseFile(database_path('data/roles.yaml'));

        foreach ($data['roles'] as $roleData) {
            Role::firstOrCreate(
                [
                    'name' => $roleData['name'],
                    'guard_name' => $roleData['guard'],
                ],
            );
        }
    }
}
