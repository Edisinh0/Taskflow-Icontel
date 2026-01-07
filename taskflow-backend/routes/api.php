<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\ClientAttachmentController;
use App\Http\Controllers\Api\ClientContactController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\IndustryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth - Única vía SuiteCRM
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rutas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/my-content', [DashboardController::class, 'getMyContent']);

        // Clientes y CRM
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('client-contacts', ClientContactController::class);
        Route::apiResource('client-attachments', ClientAttachmentController::class);
        Route::apiResource('industries', IndustryController::class);

        // Flujos y Tareas (Legacy/Internal)
        Route::apiResource('flows', FlowController::class);
        Route::apiResource('tasks', TaskController::class);
        Route::post('tasks/{id}/updates', [TaskController::class, 'addUpdate']);
        Route::apiResource('task-dependencies', TaskDependencyController::class);
        Route::apiResource('templates', TemplateController::class);
        Route::apiResource('notifications', NotificationController::class);

        // Proyectos (CrmCase)
        Route::get('/cases/stats', [CaseController::class, 'stats']);
        Route::get('/my-cases', [CaseController::class, 'myCases']);
        Route::get('/my-tasks', [TaskController::class, 'myTasks']);
        Route::apiResource('cases', CaseController::class);
        Route::post('cases/{id}/updates', [CaseController::class, 'addUpdate']);
        Route::delete('updates/{id}', [CaseController::class, 'deleteUpdate']);
        Route::post('cases/{id}/request-closure', [CaseController::class, 'requestClosure']);
        Route::post('cases/{id}/approve-closure', [CaseController::class, 'approveClosure']);
        Route::post('cases/{id}/reject-closure', [CaseController::class, 'rejectClosure']);

        // Oportunidades y Flujo de Ventas a Operaciones
        Route::get('opportunities', [OpportunityController::class, 'index']);
        Route::post('opportunities/{id}/send-to-operations', [OpportunityController::class, 'sendToOperations']);

        // Usuarios
        Route::get('/users', [UserController::class, 'index']);
    });
});