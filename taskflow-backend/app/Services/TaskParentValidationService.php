<?php

namespace App\Services;

use App\Models\Task;
use App\Models\CrmCase;
use Illuminate\Support\Facades\Log;

class TaskParentValidationService
{
    /**
     * Validar que un parent_id sea válido y exista
     * OPTIMIZACIÓN: Prevenir tareas "huérfanas" que SuiteCRM no puede asociar
     *
     * @param string|int|null $parentId ID del parent en SuiteCRM
     * @param string $parentType Tipo de parent (Cases, Tasks, etc)
     * @return array ['valid' => bool, 'error' => string|null, 'parent' => Model|null]
     */
    public function validateParentId($parentId, string $parentType): array
    {
        // Validación básica: parent_id no puede estar vacío si se especifica parent_type
        if (!$parentId && $parentType) {
            Log::warning('Parent ID is empty but parent type specified', [
                'parent_type' => $parentType,
                'parent_id' => $parentId
            ]);

            return [
                'valid' => false,
                'error' => "parent_id no puede estar vacío si se especifica parent_type: {$parentType}",
                'parent' => null
            ];
        }

        // Si no hay parent_id ni parent_type, es válido (tarea raíz)
        if (!$parentId && !$parentType) {
            return [
                'valid' => true,
                'error' => null,
                'parent' => null
            ];
        }

        // Validar según tipo de parent
        switch ($parentType) {
            case 'Cases':
                return $this->validateCaseParent($parentId);
            case 'Tasks':
                return $this->validateTaskParent($parentId);
            default:
                Log::warning('Unknown parent type', [
                    'parent_type' => $parentType,
                    'parent_id' => $parentId
                ]);

                return [
                    'valid' => false,
                    'error' => "Tipo de parent no reconocido: {$parentType}",
                    'parent' => null
                ];
        }
    }

    /**
     * Validar que el parent sea un Caso válido
     *
     * @param string|int $caseId
     * @return array
     */
    private function validateCaseParent($caseId): array
    {
        // Buscar por sweetcrm_id primero
        $case = CrmCase::where('sweetcrm_id', $caseId)->first();

        if ($case) {
            Log::debug('Valid case parent found', [
                'case_id' => $caseId,
                'local_id' => $case->id,
                'case_number' => $case->case_number
            ]);

            return [
                'valid' => true,
                'error' => null,
                'parent' => $case
            ];
        }

        // Si no se encuentra por sweetcrm_id, buscar por ID local
        $case = CrmCase::find($caseId);

        if ($case) {
            Log::debug('Valid case parent found by local ID', [
                'local_id' => $case->id,
                'case_number' => $case->case_number
            ]);

            return [
                'valid' => true,
                'error' => null,
                'parent' => $case
            ];
        }

        Log::warning('Case parent not found', [
            'case_id' => $caseId
        ]);

        return [
            'valid' => false,
            'error' => "El caso padre con ID '{$caseId}' no existe",
            'parent' => null
        ];
    }

    /**
     * Validar que el parent sea una Tarea válida
     *
     * @param string|int $taskId
     * @return array
     */
    private function validateTaskParent($taskId): array
    {
        // Buscar por sweetcrm_id primero
        $task = Task::where('sweetcrm_id', $taskId)->first();

        if ($task) {
            Log::debug('Valid task parent found', [
                'task_id' => $taskId,
                'local_id' => $task->id,
                'task_title' => $task->title
            ]);

            return [
                'valid' => true,
                'error' => null,
                'parent' => $task
            ];
        }

        // Si no se encuentra por sweetcrm_id, buscar por ID local
        $task = Task::find($taskId);

        if ($task) {
            Log::debug('Valid task parent found by local ID', [
                'local_id' => $task->id,
                'task_title' => $task->title
            ]);

            return [
                'valid' => true,
                'error' => null,
                'parent' => $task
            ];
        }

        Log::warning('Task parent not found', [
            'task_id' => $taskId
        ]);

        return [
            'valid' => false,
            'error' => "La tarea padre con ID '{$taskId}' no existe",
            'parent' => null
        ];
    }

    /**
     * Validar consistencia de task padre-hijo
     * Asegura que no haya ciclos de dependencia
     *
     * @param Task $childTask Tarea hija
     * @param Task|null $parentTask Tarea padre
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateParentChildRelationship(Task $childTask, ?Task $parentTask): array
    {
        // Si no hay parent, no hay nada que validar
        if (!$parentTask) {
            return [
                'valid' => true,
                'error' => null
            ];
        }

        // Validación: La tarea padre no puede ser la misma que la tarea hija
        if ($childTask->id === $parentTask->id) {
            Log::warning('Task cannot be parent of itself', [
                'task_id' => $childTask->id
            ]);

            return [
                'valid' => false,
                'error' => 'Una tarea no puede ser padre de sí misma'
            ];
        }

        // Validación: Evitar ciclos de dependencia
        // Verificar que el parent no sea un descendiente de la tarea actual
        if ($this->isDescendant($childTask, $parentTask)) {
            Log::warning('Circular parent-child relationship detected', [
                'child_task_id' => $childTask->id,
                'parent_task_id' => $parentTask->id
            ]);

            return [
                'valid' => false,
                'error' => 'No se puede asignar esta tarea como padre porque causaría una dependencia circular'
            ];
        }

        Log::debug('Parent-child relationship is valid', [
            'parent_task_id' => $parentTask->id,
            'child_task_id' => $childTask->id
        ]);

        return [
            'valid' => true,
            'error' => null
        ];
    }

    /**
     * Verificar si una tarea es descendiente de otra
     *
     * @param Task $ancestor Tarea potencialmente ancestro
     * @param Task $descendant Tarea potencialmente descendiente
     * @return bool
     */
    private function isDescendant(Task $ancestor, Task $descendant): bool
    {
        // Obtener todos los ancestros de descendant
        $current = $descendant;
        $visited = [];

        while ($current->parent_task_id) {
            // Evitar ciclos infinitos
            if (isset($visited[$current->parent_task_id])) {
                Log::warning('Infinite loop detected in task hierarchy', [
                    'task_id' => $current->id,
                    'parent_id' => $current->parent_task_id
                ]);
                break;
            }

            $visited[$current->parent_task_id] = true;

            // Si encontramos el ancestro en la cadena, hay relación
            if ($current->parent_task_id === $ancestor->id) {
                return true;
            }

            // Cargar siguiente padre
            $current = Task::find($current->parent_task_id);
            if (!$current) {
                break;
            }
        }

        return false;
    }

    /**
     * Limpiar y normalizar parent_id y parent_type
     * Asegura que sean válidos antes de guardar en BD
     *
     * @param mixed $parentId
     * @param string|null $parentType
     * @return array ['parent_id' => mixed, 'parent_type' => string|null]
     */
    public function normalizeParentData($parentId, ?string $parentType): array
    {
        // Si no hay parent_id, limpiar también parent_type
        if (!$parentId) {
            return [
                'parent_id' => null,
                'parent_type' => null
            ];
        }

        // Si hay parent_id pero no parent_type, intentar detectar
        if ($parentId && !$parentType) {
            Log::warning('Parent ID provided without parent type, attempting to detect', [
                'parent_id' => $parentId
            ]);

            // Intentar detectar si es un Caso o Tarea
            if (CrmCase::where('sweetcrm_id', $parentId)->exists() ||
                CrmCase::find($parentId)) {
                $parentType = 'Cases';
            } elseif (Task::where('sweetcrm_id', $parentId)->exists() ||
                Task::find($parentId)) {
                $parentType = 'Tasks';
            }
        }

        return [
            'parent_id' => $parentId,
            'parent_type' => $parentType
        ];
    }

    /**
     * Generar una descripción de error personalizada para SuiteCRM
     *
     * @param string $error Mensaje de error
     * @return string Descripción formateada
     */
    public function formatSuitecrmErrorMessage(string $error): string
    {
        return "[VALIDACIÓN TASKFLOW] " . $error;
    }
}
