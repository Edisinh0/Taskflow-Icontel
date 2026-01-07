<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\IndustryController;
use App\Http\Controllers\Api\ClientContactController;
use App\Http\Controllers\Api\ClientAttachmentController;
use App\Http\Controllers\Api\SweetCrmController;
use App\Http\Controllers\Api\CaseController;

/*
|--------------------------------------------------------------------------
| API Routes - TaskFlow v1
|--------------------------------------------------------------------------
|
| Incluye módulos SRP:
| - Flow Builder: Diseño de flujos (PM/Admin)
| - Task Center: Ejecución de tareas (Users)
|
*/

// Ruta de bienvenida de la API
Route::get('/', function () {
    return response()->json([
        'name' => 'TaskFlow API',
        'version' => 'v1',
        'status' => 'active',
        'endpoints' => [
            'auth' => '/api/v1/auth/*',
            'users' => '/api/v1/users',
            'templates' => '/api/v1/templates',
            'flows' => '/api/v1/flows',
            'tasks' => '/api/v1/tasks',
            'notifications' => '/api/v1/notifications',
            'reports' => '/api/v1/reports',
        ],
        'documentation' => '/api/v1/docs',
    ]);
});

// Rutas públicas (sin autenticación)
Route::prefix('v1')->group(function () {
    // Autenticación
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/sweetcrm-login', [AuthController::class, 'sweetCrmLogin']);

    // Industrias (catálogo público)
    Route::get('/industries', [IndustryController::class, 'index']);
});

// Broadcasting authentication routes
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// ===== NUEVOS MÓDULOS SRP =====
// Requieren autenticación y verifican roles mediante Policies
Route::prefix('v1')->group(function () {
    // Flow Builder (PM/Admin)
    require __DIR__.'/flow-builder.php';

    // Task Center (Usuarios)
    require __DIR__.'/task-center.php';
});

// Rutas protegidas (requieren autenticación)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Usuarios
    Route::get('/users', [App\Http\Controllers\Api\UserController::class, 'index']);

    // Templates
    Route::post('/templates/from-flow/{flowId}', [TemplateController::class, 'createFromFlow']);
    Route::apiResource('templates', TemplateController::class);

    // Clients
    Route::apiResource('clients', ClientController::class);
    // Plantillas recomendadas por industria del cliente
    Route::get('/clients/{client}/recommended-templates', [ClientController::class, 'recommendedTemplates']);

    // Flows
    Route::apiResource('flows', FlowController::class);

    // Tasks
    Route::post('/tasks/reorder', [TaskController::class, 'reorder']);
    Route::post('/tasks/{id}/move', [TaskController::class, 'move']);
    Route::apiResource('tasks', TaskController::class);
    // Dependencias de tareas
    Route::get('/tasks/{taskId}/dependencies', [App\Http\Controllers\Api\TaskDependencyController::class, 'index']);
    Route::post('/tasks/{taskId}/dependencies', [App\Http\Controllers\Api\TaskDependencyController::class, 'store']);
    Route::delete('/dependencies/{id}', [App\Http\Controllers\Api\TaskDependencyController::class, 'destroy']);
    Route::get('/tasks/{taskId}/check-blocked', [App\Http\Controllers\Api\TaskDependencyController::class, 'checkBlocked']);
    
    // Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/stats', [NotificationController::class, 'stats']);

    // Reportes
    Route::get('/reports', [App\Http\Controllers\Api\ReportController::class, 'index']);
    Route::get('/reports/stats', [App\Http\Controllers\Api\ReportController::class, 'stats']);
    Route::get('/reports/export/csv', [App\Http\Controllers\Api\ReportController::class, 'exportCsv']);
    Route::get('/reports/export/pdf', [App\Http\Controllers\Api\ReportController::class, 'exportPdf']);

    // Adjuntos de Tareas
    Route::post('/tasks/{task}/attachments', [App\Http\Controllers\Api\TaskAttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [App\Http\Controllers\Api\TaskAttachmentController::class, 'destroy']);

    // Clientes - Contactos y Adjuntos
    Route::post('/clients/{client}/contacts', [ClientContactController::class, 'store'])->name('clients.contacts.store');
    Route::put('/contacts/{contact}', [ClientContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [ClientContactController::class, 'destroy'])->name('contacts.destroy');
    
    Route::post('/clients/{client}/attachments', [ClientAttachmentController::class, 'store'])->name('clients.attachments.store');
    Route::delete('/clients/{client}/attachments/{attachment}', [ClientAttachmentController::class, 'destroy'])->name('clients.attachments.destroy');

    // SweetCRM Integration
    Route::prefix('sweetcrm')->group(function () {
        Route::get('/ping', [SweetCrmController::class, 'ping']);
        Route::post('/sync-clients', [SweetCrmController::class, 'syncClients']);
        Route::post('/sync-client/{sweetcrmId}', [SweetCrmController::class, 'syncClient']);
        Route::get('/user/{sweetcrmId}', [SweetCrmController::class, 'getUser']);
        Route::post('/sync-me', [SweetCrmController::class, 'syncCurrentUser']);
    });

    // CRM Cases
    Route::get('/cases/stats', [CaseController::class, 'stats']);
    Route::apiResource('cases', CaseController::class);
});