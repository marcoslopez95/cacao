<?php

namespace Database\Seeders\Demo;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoProfessorsSeeder extends Seeder
{
    /**
     * Seed professor accounts and department assignments.
     */
    public function run(): void
    {
        $profesorRole = Role::where('name', 'Profesor')->where('guard_name', 'web')->firstOrFail();

        $professors = [
            ['num' => '01', 'name' => 'Carlos Mendoza'],
            ['num' => '02', 'name' => 'Ana García'],
            ['num' => '03', 'name' => 'Luis Rodríguez'],
            ['num' => '04', 'name' => 'María Torres'],
            ['num' => '05', 'name' => 'Pedro Gómez'],
            ['num' => '06', 'name' => 'Carmen López'],
            ['num' => '07', 'name' => 'José Martínez'],
            ['num' => '08', 'name' => 'Elena Vargas'],
            ['num' => '09', 'name' => 'Roberto Díaz'],
            ['num' => '10', 'name' => 'Patricia Sánchez'],
            ['num' => '11', 'name' => 'Miguel Herrera'],
            ['num' => '12', 'name' => 'Laura Morales'],
        ];

        foreach ($professors as $profesorData) {
            $email = "prof{$profesorData['num']}@utcacao.edu.ve";

            // Create or get user
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $profesorData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            // Assign Profesor role
            $user->syncRoles([$profesorRole->name]);

            // Create or get professor record
            Professor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'weekly_hour_limit' => 20,
                    'active' => true,
                ],
            );
        }
    }
}
