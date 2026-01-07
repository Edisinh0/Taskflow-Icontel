<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates a default system user with ID=1 to satisfy foreign key constraints.
     * This user is used as a fallback for cases/flows/tasks that were created
     * before user sync or when the original creator is not available.
     */
    public function run(): void
    {
        // Check if user with ID 1 already exists
        $existingUser = DB::table('users')->where('id', 1)->first();

        if (!$existingUser) {
            DB::table('users')->insert([
                'id' => 1,
                'name' => 'Sistema TaskFlow',
                'email' => 'sistema@taskflow.local',
                'password' => Hash::make('sistema_taskflow_' . bin2hex(random_bytes(8))),
                'role' => 'admin',
                'department' => 'Sistema',
                'is_active' => true,
                'sweetcrm_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✅ Usuario del sistema (ID=1) creado exitosamente');
        } else {
            $this->command->info('ℹ️  Usuario del sistema (ID=1) ya existe');
        }
    }
}
