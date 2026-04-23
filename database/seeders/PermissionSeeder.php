<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Yaml\Yaml;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $data = Yaml::parseFile(database_path('data/permissions.yaml'));

        foreach ($data['permissions'] ?? [] as $permissionData) {
            Permission::firstOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => $permissionData['guard'],
                ],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
