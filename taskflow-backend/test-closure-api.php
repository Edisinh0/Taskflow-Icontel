<?php

// Test script for Case Closure API
// Usage: php test-closure-api.php

require __DIR__ . '/vendor/autoload.php';

// Load Laravel app
$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\CrmCase;
use App\Models\CaseClosureRequest;

echo "=== Case Closure API Test Script ===\n\n";

// 1. Get available users
echo "📋 Available Users:\n";
$users = User::select('id', 'name', 'email', 'department')->get();
foreach ($users as $user) {
    echo "  [{$user->id}] {$user->name} ({$user->email}) - Dept: {$user->department}\n";
}

echo "\n📋 Jefes de Área (SAC):\n";
$jefes = User::where('department', 'SAC')->select('id', 'name', 'email')->get();
foreach ($jefes as $jefe) {
    echo "  [{$jefe->id}] {$jefe->name} ({$jefe->email})\n";
}

// 2. Get available cases
echo "\n📋 Available Cases:\n";
$cases = CrmCase::select('id', 'case_number', 'subject', 'status', 'closure_status')
    ->where('closure_status', 'open')
    ->limit(5)
    ->get();

if ($cases->count() > 0) {
    foreach ($cases as $case) {
        echo "  [Case {$case->id}] {$case->case_number} - {$case->subject}\n";
        echo "      Status: {$case->status}, Closure: {$case->closure_status}\n";
    }
} else {
    echo "  No open cases found\n";
}

// 3. Get closure requests
echo "\n📋 Pending Closure Requests:\n";
$requests = CaseClosureRequest::where('status', 'pending')
    ->with('case', 'requestedBy', 'assignedTo')
    ->get();

if ($requests->count() > 0) {
    foreach ($requests as $req) {
        echo "  [Request {$req->id}] Case {$req->case_id}\n";
        echo "      Requested by: {$req->requestedBy->name}\n";
        echo "      Status: {$req->status}\n";
    }
} else {
    echo "  No pending requests\n";
}

echo "\n✅ Test script completed\n";
