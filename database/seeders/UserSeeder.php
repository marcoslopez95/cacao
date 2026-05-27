<?php

namespace Database\Seeders;

use App\Enums\EducationalLevel;
use App\Models\Guardian;
use App\Models\Professor;
use App\Models\Student;
use App\Models\User;
use App\Models\UserConsent;
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
                $this->ensureSubRecord($existing, $userData['role']);
                $this->ensureConsent($existing);

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
            $this->ensureSubRecord($user, $userData['role']);
            $this->ensureConsent($user);
        }
    }

    private function ensureConsent(User $user): void
    {
        if (UserConsent::where('user_id', $user->id)->whereNull('revoked_at')->exists()) {
            return;
        }

        UserConsent::create([
            'user_id' => $user->id,
            'policy_version' => 'v1.0',
            'accepts_data_processing' => true,
            'accepts_image_use' => true,
            'accepts_whatsapp_contact' => true,
            'accepts_email_contact' => true,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'seeder',
            'granted_at' => now(),
            'revoked_at' => null,
        ]);
    }

    private function ensureSubRecord(User $user, string $roleName): void
    {
        match ($roleName) {
            'Profesor' => Professor::firstOrCreate(['user_id' => $user->id]),
            'Estudiante' => Student::firstOrCreate(
                ['user_id' => $user->id],
                ['educational_level' => EducationalLevel::University]
            ),
            'Representante' => Guardian::firstOrCreate(['user_id' => $user->id]),
            default => null,
        };
    }
}
