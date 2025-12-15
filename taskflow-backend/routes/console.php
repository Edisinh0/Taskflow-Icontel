<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar verificación de tareas vencidas
use Illuminate\Support\Facades\Schedule;

Schedule::command('tasks:check-overdue')->hourly();
