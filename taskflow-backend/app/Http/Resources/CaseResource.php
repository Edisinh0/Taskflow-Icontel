<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para CrmCase - Solo incluye campos necesarios para la lista
 * Esto reduce significativamente el tamaño del JSON y mejora el rendimiento
 */
class CaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'case_number' => $this->case_number,
            'subject' => $this->subject,
            'status' => $this->status,
            'priority' => $this->priority,
            'type' => $this->type,
            'area' => $this->area,
            
            // Conteo de tareas sin cargar toda la relación
            'tasks_count' => $this->whenCounted('tasks', $this->tasks_count ?? 0),
            
            // Cliente: solo los campos esenciales
            'client' => $this->whenLoaded('client', function () {
                return $this->client ? [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ] : null;
            }),
            
            // Usuario asignado: solo los campos esenciales
            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return $this->assignedUser ? [
                    'id' => $this->assignedUser->id,
                    'name' => $this->assignedUser->name,
                    'department' => $this->assignedUser->department ?? null,
                ] : null;
            }),
            
            // Fechas relevantes
            'created_at' => $this->created_at?->toISOString(),
            'sweetcrm_created_at' => $this->sweetcrm_created_at?->toISOString(),
            'sweetcrm_synced_at' => $this->sweetcrm_synced_at?->toISOString(),
        ];
    }
}
