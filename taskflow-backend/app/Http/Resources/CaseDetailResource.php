<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para detalle completo de un CrmCase
 * Incluye descripción completa y tareas relacionadas
 */
class CaseDetailResource extends JsonResource
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
            // Decodificar entidades HTML (dos veces por seguridad si vienen de SweetCRM muy escapados)
            'description' => html_entity_decode(html_entity_decode($this->description ?? '')),
            'status' => $this->status,
            'priority' => $this->priority,
            'type' => $this->type,
            'area' => $this->area,
            'sweetcrm_id' => $this->sweetcrm_id,
            
            // Información de personas (Requerimiento visual)
            'original_creator_name' => $this->original_creator_name,
            'assigned_user_name' => $this->assigned_user_name,
            
            // Estado de solicitud de cierre (integrado con nuevo sistema)
            'closure_info' => [
                'requested' => $this->closure_status === 'closure_requested',
                'requested_at' => $this->closure_requested_at?->toISOString(),
                'requested_by' => $this->whenLoaded('closureRequestedBy', function () {
                    return $this->closureRequestedBy ? [
                        'id' => $this->closureRequestedBy->id,
                        'name' => $this->closureRequestedBy->name
                    ] : null;
                }),
                'closure_request_id' => $this->whenLoaded('latestClosureRequest', function () {
                    return $this->latestClosureRequest?->first()?->id;
                }),
            ],

            // Nuevo: Estado de cierre
            'closure_status' => $this->closure_status,

            // Nuevo: Información del aprobador
            'closure_approved_by' => $this->whenLoaded('closureApprovedBy', function () {
                return $this->closureApprovedBy ? [
                    'id' => $this->closureApprovedBy->id,
                    'name' => $this->closureApprovedBy->name
                ] : null;
            }),

            'closure_approved_at' => $this->closure_approved_at?->toISOString(),
            
            // Cliente completo
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email ?? null,
                ];
            }),
            
            // Usuario asignado (relación)
            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return [
                    'id' => $this->assignedUser->id,
                    'name' => $this->assignedUser->name,
                    'email' => $this->assignedUser->email,
                    'department' => $this->assignedUser->department ?? null,
                ];
            }),
            
            // Tareas
            'tasks' => $this->whenLoaded('tasks', function () {
                return $this->tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'description' => $task->description,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'progress' => $task->progress ?? 0,
                        'due_date' => $task->sla_due_date ? $task->sla_due_date->toISOString() : null,
                        'estimated_start_at' => $task->estimated_start_at ? $task->estimated_start_at->toISOString() : null,
                        'estimated_end_at' => $task->estimated_end_at ? $task->estimated_end_at->toISOString() : null,
                        'actual_start_at' => $task->actual_start_at ? $task->actual_start_at->toISOString() : null,
                        'actual_end_at' => $task->actual_end_at ? $task->actual_end_at->toISOString() : null,
                        'created_at' => $task->created_at ? $task->created_at->toISOString() : null,
                        'sweetcrm_synced_at' => $task->sweetcrm_synced_at ? $task->sweetcrm_synced_at->toISOString() : null,
                        'assignee' => $task->assignee ? [
                            'id' => $task->assignee->id,
                            'name' => $task->assignee->name,
                        ] : null,
                    ];
                });
            }),

            // Avances (Timeline)
            'updates' => $this->whenLoaded('updates', function () {
                return $this->updates->map(function ($update) {
                    return [
                        'id' => $update->id,
                        'content' => $update->content,
                        'type' => $update->type,
                        'created_at' => $update->created_at->toISOString(),
                        'formatted_date' => $update->created_at->format('d/m/Y H:i'),
                        'user' => [
                            'id' => $update->user->id,
                            'name' => $update->user->name,
                            'initials' => substr($update->user->name, 0, 2),
                        ],
                        'type_icon' => $update->type_icon,
                        'type_color' => $update->type_color,
                        'attachments' => $update->attachments->map(function($att) {
                            return [
                                'id' => $att->id,
                                'name' => $att->name,
                                'file_path' => $att->file_path,
                                'url' => \Illuminate\Support\Facades\Storage::url($att->file_path),
                            ];
                        })
                    ];
                });
            }),
            
            // Fechas
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'sweetcrm_synced_at' => $this->sweetcrm_synced_at?->toISOString(),
        ];
    }
}
