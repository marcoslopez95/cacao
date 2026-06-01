<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminTeamSeeder extends Seeder
{
    public function run(): void
    {
        $adminTeam = Team::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administración', 'is_personal' => false]
        );

        $admins = User::role('Admin')->get();

        foreach ($admins as $admin) {
            if (! $admin->belongsToTeam($adminTeam)) {
                $adminTeam->members()->attach($admin, ['role' => TeamRole::Admin->value]);
            }
        }
    }
}
