<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Task;
use App\Observers\TaskObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    \Log::info('🚀 AppServiceProvider::boot() ejecutándose');
    
    Task::observe(TaskObserver::class);
    
    \Log::info('✅ TaskObserver registrado correctamente');
    }
}
