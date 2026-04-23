<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Yaml\Yaml;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $data = Yaml::parseFile(database_path('data/users.yaml'));

        foreach ($data['users'] as $userData) {
            $existing = User::where('email', $userData['email'])->first();

            if ($existing) {
                $existing->syncRoles([$userData['role']]);

                continue;
            }

            $user = User::factory()->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$userData['role']]);
        }
    }
}
