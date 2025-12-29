<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncSugarCrmUsers extends Command
{
    protected $signature = 'sweetcrm:sync-users
                            {username? : SugarCRM username}
                            {password? : SugarCRM password}';

    protected $description = 'Sincronizar usuarios desde SugarCRM a Taskflow';

    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        parent::__construct();
        $this->sweetCrmService = $sweetCrmService;
    }

    public function handle()
    {
        $this->info('🔄 Sincronizando usuarios desde SugarCRM...');

        $username = $this->argument('username') ?? config('services.sweetcrm.username');
        $password = $this->argument('password') ?? config('services.sweetcrm.password');

        if (!$username || !$password) {
            $this->error('❌ Faltan credenciales (username/password). Proporciónalas como argumentos o configúralas en .env');
            return 1;
        }

        $sessionId = $this->sweetCrmService->getSessionId($username, $password);

        if (!$sessionId) {
            $this->error('❌ Error de autenticación.');
            return 1;
        }

        $sugarUsers = $this->sweetCrmService->getUsers($sessionId);
        $this->info("   ✅ " . count($sugarUsers) . " usuarios obtenidos");

        $bar = $this->output->createProgressBar(count($sugarUsers));
        $bar->start();

        $created = 0;
        $updated = 0;

        foreach ($sugarUsers as $sUser) {
            $vals = $sUser['name_value_list'];
            $sweetId = $sUser['id'];
            
            $email = $vals['email1']['value'] ?? null;
            $userName = $vals['user_name']['value'] ?? null;
            $firstName = $vals['first_name']['value'] ?? '';
            $lastName = $vals['last_name']['value'] ?? '';
            $fullName = trim($firstName . ' ' . $lastName) ?: $userName;
            $isAdmin = ($vals['is_admin']['value'] ?? '0') === '1';

            if (!$email && !$sweetId) continue;

            // 1. Prioridad: Buscar por ID directo de SweetCRM
            $user = User::where('sweetcrm_id', $sweetId)->first();

            // 2. Si no existe por ID, buscar por email (para vincular usuarios creados manualmente)
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
                
                // Si el usuario existe por email pero TIENE OTRO sweetcrm_id, hay un conflicto
                if ($user && $user->sweetcrm_id && $user->sweetcrm_id !== $sweetId) {
                    $this->warn("⚠️  Conflicto: El email {$email} ya tiene el ID {$user->sweetcrm_id}, pero Sugar reporta el ID {$sweetId}. Saltando...");
                    continue;
                }
            }

            $userData = [
                'name' => $fullName,
                'email' => $email,
                'department' => $vals['department']['value'] ?? null,
                'sweetcrm_id' => $sweetId,
                'sweetcrm_user_type' => $isAdmin ? 'administrator' : 'regular',
                'sweetcrm_synced_at' => now(),
                'role' => $isAdmin ? 'admin' : 'user',
            ];

            if ($user) {
                // Si encontramos al usuario (por ID o por Email)
                // Solo actualizamos el email si el usuario no tiene uno o si lo buscamos por ID
                if ($user->sweetcrm_id !== $sweetId) {
                    // Si lo encontramos por email y estamos vinculando el ID por primera vez
                    $user->update(['sweetcrm_id' => $sweetId]);
                }

                unset($userData['email']);
                $user->update($userData);
                $updated++;
            } else {
                // Crear nuevo - usar updateOrCreate para evitar duplicados
                $userData['password'] = Hash::make(Str::random(16));

                try {
                    User::create($userData);
                    $created++;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Si hay error de duplicado, intentar actualizar
                    if ($e->getCode() === '23000') {
                        $this->warn("⚠️  Usuario {$sweetId} ya existe (posible duplicado). Intentando actualizar...");
                        $existingUser = User::where('sweetcrm_id', $sweetId)->first();
                        if ($existingUser) {
                            unset($userData['email']);
                            unset($userData['password']);
                            $existingUser->update($userData);
                            $updated++;
                        }
                    } else {
                        throw $e;
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Sincronización completada: {$created} creados, {$updated} actualizados.");

        return 0;
    }
}
