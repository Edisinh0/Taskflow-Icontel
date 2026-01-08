<?php

// Setup script for closure testing
// Usage: php setup-closure-test.php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Setting up Closure System Test Data ===\n\n";

// 1. Create jefes de área if they don't exist
echo "👤 Creating Jefes de Área...\n";

$jefes = [
    [
        'name' => 'María José Araneda',
        'email' => 'maria.araneda@icontel.cl',
        'password' => 'password123',
        'department' => 'SAC',
        'sweetcrm_id' => 'maria001'
    ],
    [
        'name' => 'Daniela Araneda',
        'email' => 'daniela.araneda@icontel.cl',
        'password' => 'password123',
        'department' => 'SAC',
        'sweetcrm_id' => 'daniela001'
    ]
];

foreach ($jefes as $jefe) {
    $existing = User::where('email', $jefe['email'])->first();
    
    if ($existing) {
        echo "  ✓ {$jefe['name']} already exists (ID: {$existing->id})\n";
    } else {
        $user = User::create([
            'name' => $jefe['name'],
            'email' => $jefe['email'],
            'password' => Hash::make($jefe['password']),
            'department' => $jefe['department'],
            'sweetcrm_id' => $jefe['sweetcrm_id'],
            'email_verified_at' => now()
        ]);
        echo "  ✅ Created {$jefe['name']} (ID: {$user->id})\n";
    }
}

// 2. List all users for reference
echo "\n📋 Current Users:\n";
$users = User::select('id', 'name', 'email', 'department')->get();
foreach ($users as $user) {
    echo "  [{$user->id}] {$user->name} ({$user->email}) - {$user->department}\n";
}

echo "\n✅ Setup completed\n";
