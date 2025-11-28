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
        User::create([
            'name' => 'Admin TaskFlow',
            'email' => 'admin@taskflow.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Project Manager
        User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@taskflow.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Técnico 1
        User::create([
            'name' => 'María González',
            'email' => 'maria.gonzalez@taskflow.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Técnico 2
        User::create([
            'name' => 'Carlos Rodríguez',
            'email' => 'carlos.rodriguez@taskflow.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        echo "✅ Usuarios creados exitosamente\n";
    }
}