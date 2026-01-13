<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseClosureRequestController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\CaseValidationController;
use App\Http\Controllers\Api\ClientAttachmentController;
use App\Http\Controllers\Api\ClientContactController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CrmSearchController;
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
        Route::get('/dashboard/area-content', [DashboardController::class, 'getAreaBasedContent']);
        Route::get('/dashboard/delegated', [DashboardController::class, 'getDelegated']);
        Route::get('/dashboard/delegated-sales', [DashboardController::class, 'getDelegatedSales']);
        Route::get('/dashboard/crm-overview', [DashboardController::class, 'getCrmOverview']);

        // Clientes y CRM
        Route::apiResource('clients', ClientController::class);
        Route::get('clients/{client}/full-history', [ClientController::class, 'getFullHistory']);
        Route::get('clients/{client}/recommended-templates', [ClientController::class, 'recommendedTemplates']);
        Route::apiResource('client-contacts', ClientContactController::class);
        Route::apiResource('client-attachments', ClientAttachmentController::class);
        Route::apiResource('industries', IndustryController::class);

        // Flujos y Tareas (Legacy/Internal)
        Route::apiResource('flows', FlowController::class);
        Route::apiResource('tasks', TaskController::class);
        Route::post('tasks/{id}/updates', [TaskController::class, 'addUpdate']);
        Route::apiResource('task-dependencies', TaskDependencyController::class);
        Route::apiResource('templates', TemplateController::class);
        // Notificaciones
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::apiResource('notifications', NotificationController::class);

        // Solicitudes de Cierre de Casos
        Route::get('/closure-requests', [CaseClosureRequestController::class, 'index']);
        Route::get('/closure-requests/{closureRequest}', [CaseClosureRequestController::class, 'show']);
        Route::post('/cases/{caseId}/request-closure', [CaseClosureRequestController::class, 'store']);
        Route::post('/closure-requests/{closureRequest}/approve', [CaseClosureRequestController::class, 'approve']);
        Route::post('/closure-requests/{closureRequest}/reject', [CaseClosureRequestController::class, 'reject']);
        Route::get('/cases/{caseId}/closure-request', [CaseClosureRequestController::class, 'getCaseClosureStatus']);

        // Proyectos (CrmCase)
        Route::get('/cases/stats', [CaseController::class, 'stats']);
        Route::get('/my-cases', [CaseController::class, 'myCases']);
        Route::get('/my-tasks', [TaskController::class, 'myTasks']);
        Route::apiResource('cases', CaseController::class);
        Route::post('cases/{id}/updates', [CaseController::class, 'addUpdate']);
        Route::delete('updates/{id}', [CaseController::class, 'deleteUpdate']);

        // Workflow: Case Validation (Operaciones)
        Route::prefix('cases')->group(function () {
            Route::get('/validation/pending', [CaseValidationController::class, 'pendingValidation']);
            Route::get('{case}/workflow-history', [CaseValidationController::class, 'getWorkflowHistory']);
            Route::post('{case}/handover-to-validation', [CaseValidationController::class, 'handoverToValidation']);
            Route::post('{case}/validate/approve', [CaseValidationController::class, 'approve']);
            Route::post('{case}/validate/reject', [CaseValidationController::class, 'reject']);
        });

        // Workflow: Task Delegation (Operaciones)
        Route::prefix('tasks')->group(function () {
            Route::get('/delegated', [TaskController::class, 'getDelegatedTasks']);
            Route::post('{task}/delegate', [TaskController::class, 'delegate']);
            Route::post('{task}/complete-delegation', [TaskController::class, 'completeDelegation']);
        });

        // Oportunidades y Flujo de Ventas a Operaciones
        Route::get('opportunities/stats', [OpportunityController::class, 'stats']);
        Route::apiResource('opportunities', OpportunityController::class);
        Route::post('opportunities/{id}/send-to-operations', [OpportunityController::class, 'sendToOperations']);
        Route::post('opportunities/{id}/updates', [OpportunityController::class, 'addUpdate']);

        // Usuarios
        Route::get('/users', [UserController::class, 'index']);

        // CRM Search (for task creation linking)
        Route::get('/crm/search-entities', [CrmSearchController::class, 'searchEntities']);
    });
});