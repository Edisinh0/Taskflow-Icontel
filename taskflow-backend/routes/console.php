<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar verificación de tareas vencidas
use Illuminate\Support\Facades\Schedule;

Schedule::command('tasks:check-overdue')->hourly();

// Programar verificación de SLA - Ejecutar cada hora
Schedule::command('sla:check')->hourly();

// Sincronización automática de SweetCRM
Schedule::command('sweetcrm:sync-users')->dailyAt('03:00'); // Sincroniza usuarios una vez al día
Schedule::command('sweetcrm:sync-cases')->everyThirtyMinutes(); // Sincroniza casos cada 30 minutos
