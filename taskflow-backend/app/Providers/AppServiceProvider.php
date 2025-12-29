<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Task;
use App\Models\Flow;
use App\Models\Client;
use App\Observers\FlowObserver;
use App\Policies\TaskPolicy;
use App\Policies\FlowPolicy;
use App\Policies\ClientPolicy;

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

        // Registrar Observers
        Task::observe([
            \App\Observers\TaskLifecycleObserver::class,
            \App\Observers\TaskNotificationObserver::class,
            \App\Observers\TaskProgressObserver::class,
        ]);
        Flow::observe(FlowObserver::class);

        // Registrar Policies
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Flow::class, FlowPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);

        \Log::info('✅ Observers y Policies registrados correctamente');
    }
}
