<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes - TaskFlow v1
|--------------------------------------------------------------------------
*/

// Rutas públicas (sin autenticación)
Route::prefix('v1')->group(function () {
    // Autenticación
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
});

// Rutas protegidas (requieren autenticación)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Templates
    Route::apiResource('templates', TemplateController::class);

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

    // Reordenamiento de tareas
Route::post('/tasks/reorder', [TaskController::class, 'reorder']);
Route::post('/tasks/{id}/move', [TaskController::class, 'move']);
});