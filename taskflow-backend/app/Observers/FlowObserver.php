<?php

namespace App\Observers;

use App\Models\Flow;
use Illuminate\Support\Facades\Log;

class FlowObserver
{
    /**
     * Handle the Flow "saving" event.
     */
    public function saving(Flow $flow): void
    {
        if (auth()->check()) {
            $flow->last_updated_by = auth()->id();
        }
    }

    /**
     * Handle the Flow "deleted" event.
     * Soft-delete all tasks associated with the flow.
     */
    public function deleted(Flow $flow): void
    {
        Log::info('🗑️ Flow eliminado, eliminando tareas en cascada', ['flow_id' => $flow->id]);
        
        // Soft delete de todas las tareas asociadas
        $flow->tasks()->delete();
    }

    /**
     * Handle the Flow "restored" event.
     * Restore all tasks associated with the flow.
     */
    public function restored(Flow $flow): void
    {
        Log::info('♻️ Flow restaurado, restaurando tareas', ['flow_id' => $flow->id]);
        
        // Restaurar tareas (asumiendo que fueron borradas al mismo tiempo)
        // Nota: Esto restaurará TODAS las tareas borradas del flujo, incluso las que 
        // se borraron individualmente antes de borrar el flujo. 
        // Para una implementación más precisa se necesitaría tracking de fecha de borrado,
        // pero para este caso de uso es suficiente.
        $flow->tasks()->restore();
    }
}
