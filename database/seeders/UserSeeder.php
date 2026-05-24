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

            $nameParts = explode(' ', (string) $userData['name'], 2);

            $user = User::factory()->create([
                'first_name' => $nameParts[0] ?? $userData['name'],
                'last_name' => $nameParts[1] ?? '',
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$userData['role']]);
        }
    }
}
