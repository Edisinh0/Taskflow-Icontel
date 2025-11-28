<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;
use App\Models\User;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@taskflow.com')->first();

        // Plantilla 1: Instalación 3CX
        Template::create([
            'name' => 'Instalación 3CX',
            'description' => 'Plantilla estándar para instalación de central telefónica 3CX',
            'version' => '1.0',
            'is_active' => true,
            'created_by' => $admin->id,
            'config' => [
                'estimated_duration_days' => 5,
                'required_roles' => ['Técnico', 'Project Manager'],
                'priority' => 'high'
            ]
        ]);

        // Plantilla 2: Soporte Técnico
        Template::create([
            'name' => 'Soporte Técnico',
            'description' => 'Flujo estándar para atención de tickets de soporte',
            'version' => '1.2',
            'is_active' => true,
            'created_by' => $admin->id,
            'config' => [
                'estimated_duration_days' => 2,
                'required_roles' => ['Soporte'],
                'priority' => 'medium'
            ]
        ]);

        // Plantilla 3: Alta de Cliente
        Template::create([
            'name' => 'Alta de Cliente',
            'description' => 'Proceso de onboarding para nuevos clientes',
            'version' => '1.0',
            'is_active' => true,
            'created_by' => $admin->id,
            'config' => [
                'estimated_duration_days' => 3,
                'required_roles' => ['Ventas', 'Administración'],
                'priority' => 'high'
            ]
        ]);

        echo "✅ Plantillas creadas exitosamente\n";
    }
}