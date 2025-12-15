<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin principal
        User::updateOrCreate(
            ['email' => 'admin@taskflow.com'],
            [
                'name' => 'Admin TaskFlow',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Project Manager
        User::updateOrCreate(
            ['email' => 'juan.perez@taskflow.com'],
            [
                'name' => 'Juan Pérez',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Técnico 1
        User::updateOrCreate(
            ['email' => 'maria.gonzalez@taskflow.com'],
            [
                'name' => 'María González',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Técnico 2
        User::updateOrCreate(
            ['email' => 'carlos.rodriguez@taskflow.com'],
            [
                'name' => 'Carlos Rodríguez',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        echo "✅ Usuarios creados/actualizados exitosamente\n";
    }
}