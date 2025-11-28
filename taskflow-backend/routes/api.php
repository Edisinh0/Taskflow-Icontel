<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\TaskController;

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
    Route::apiResource('tasks', TaskController::class);
});