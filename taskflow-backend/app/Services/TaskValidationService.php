<?php

namespace App\Services;

use App\Models\CrmCase;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

/**
 * Servicio de Validación de Tareas para SuiteCRM Legacy v4.1
 *
 * Responsabilidades:
 * - Validar datos de tarea antes de crearla
 * - Validar existence de parent (Case/Opportunity)
 * - Validar formato de fechas (Y-m-d H:i:s)
 * - Construir name_value_list completo para SuiteCRM
 * - Mapear datos locales a formato SuiteCRM
 */
class TaskValidationService
{
    /**
     * Validar todos los datos de tarea requeridos
     *
     * @param array $validated Datos validados del FormRequest
     * @return array ['valid' => bool, 'errors' => string[], 'data' => array|null]
     */
    public function validateTaskData(array $validated): array
    {
        $errors = [];

        // Validaciones básicas
        if (empty($validated['title'])) {
            $errors[] = 'El título de la tarea es requerido';
        }

        if (empty($validated['parent_type'])) {
            $errors[] = 'El tipo de padre (Cases/Opportunities) es requerido';
        } elseif (!in_array($validated['parent_type'], ['Cases', 'Opportunities'])) {
            $errors[] = "Tipo de padre inválido: {$validated['parent_type']}";
        }

        if (empty($validated['parent_id'])) {
            $errors[] = 'El ID del padre es requerido';
        }

        if (empty($validated['priority'])) {
            $errors[] = 'La prioridad es requerida';
        }

        // Validar fechas
        if (empty($validated['date_start'])) {
            $errors[] = 'La fecha de inicio es requerida';
        } else {
            $dateStartError = $this->validateDateFormat(
                $validated['date_start'],
                'date_start'
            );
            if ($dateStartError) {
                $errors[] = $dateStartError;
            }
        }

        if (empty($validated['date_due'])) {
            $errors[] = 'La fecha de término es requerida';
        } else {
            $dateDueError = $this->validateDateFormat(
                $validated['date_due'],
                'date_due'
            );
            if ($dateDueError) {
                $errors[] = $dateDueError;
            }
        }

        // Validar que date_start <= date_due
        if (
            !empty($validated['date_start']) &&
            !empty($validated['date_due']) &&
            strtotime($validated['date_start']) > strtotime($validated['date_due'])
        ) {
            $errors[] = 'La fecha de inicio debe ser anterior o igual a la fecha de término';
        }

        // Validar parent existe
        if (!empty($validated['parent_type']) && !empty($validated['parent_id'])) {
            $parentRecord = $this->validateParent(
                $validated['parent_type'],
                $validated['parent_id']
            );

            if (!$parentRecord) {
                $errors[] = "Caso/Oportunidad no encontrado: {$validated['parent_id']}";
            }
        }

        if (!empty($errors)) {
            Log::warning('Task validation failed', [
                'errors' => $errors,
                'data' => [
                    'title' => $validated['title'] ?? null,
                    'parent_type' => $validated['parent_type'] ?? null,
                ]
            ]);

            return [
                'valid' => false,
                'errors' => $errors,
                'data' => null,
            ];
        }

        Log::info('Task validation passed', [
            'title' => $validated['title'],
            'parent_type' => $validated['parent_type'],
            'parent_id' => $validated['parent_id'],
        ]);

        return [
            'valid' => true,
            'errors' => [],
            'data' => $validated,
        ];
    }

    /**
     * Construir name_value_list completo para SuiteCRM v4.1
     *
     * @param array $validated Datos validados
     * @param Model $parentRecord Modelo de parent (Case u Opportunity)
     * @param Model|null $user Usuario actual (para assigned_user_id)
     * @return array name_value_list listo para enviar a SuiteCRM
     */
    public function buildNameValueList(
        array $validated,
        Model $parentRecord,
        ?Model $user = null
    ): array {
        $nameValueList = [
            // Campos requeridos
            'name' => [
                'name' => 'name',
                'value' => $validated['title']
            ],
            'priority' => [
                'name' => 'priority',
                'value' => $validated['priority']
            ],
            'status' => [
                'name' => 'status',
                'value' => $validated['status'] ?? 'Not Started'
            ],
            'date_start' => [
                'name' => 'date_start',
                'value' => $this->formatDateForSuiteCRM($validated['date_start'])
            ],
            'date_due' => [
                'name' => 'date_due',
                'value' => $this->formatDateForSuiteCRM($validated['date_due'])
            ],
            'parent_type' => [
                'name' => 'parent_type',
                'value' => $validated['parent_type']
            ],
            'parent_id' => [
                'name' => 'parent_id',
                'value' => $validated['parent_id']
            ],

            // Campos opcionales
            'description' => [
                'name' => 'description',
                'value' => $validated['description'] ?? ''
            ],
            'parent_name' => [
                'name' => 'parent_name',
                'value' => $parentRecord->subject ?? $parentRecord->name ?? ''
            ],
        ];

        // Agregar completion_percentage si se proporciona
        if (isset($validated['completion_percentage']) && $validated['completion_percentage'] !== null) {
            $nameValueList['completion_percentage'] = [
                'name' => 'completion_percentage',
                'value' => (int) $validated['completion_percentage']
            ];
        }

        // Agregar contact_id si se proporciona
        if (isset($validated['contact_id']) && $validated['contact_id']) {
            $nameValueList['contact_id'] = [
                'name' => 'contact_id',
                'value' => $validated['contact_id']
            ];
        }

        // Asignar al usuario actual o específico en request
        if (isset($validated['sweetcrm_assigned_user_id']) && $validated['sweetcrm_assigned_user_id']) {
            $nameValueList['assigned_user_id'] = [
                'name' => 'assigned_user_id',
                'value' => $validated['sweetcrm_assigned_user_id']
            ];
        } elseif ($user && $user->sweetcrm_id) {
            $nameValueList['assigned_user_id'] = [
                'name' => 'assigned_user_id',
                'value' => $user->sweetcrm_id
            ];
            $nameValueList['assigned_user_name'] = [
                'name' => 'assigned_user_name',
                'value' => $user->name
            ];
        }

        Log::info('Name_value_list built successfully', [
            'fields_count' => count($nameValueList),
            'has_assigned_user' => isset($nameValueList['assigned_user_id']),
            'has_completion' => isset($nameValueList['completion_percentage']),
        ]);

        return $nameValueList;
    }

    /**
     * Validar formato de fecha
     *
     * @param string $dateString Fecha a validar
     * @param string $fieldName Nombre del campo (para logging)
     * @return string|null Error si es inválido, null si es válido
     */
    private function validateDateFormat(string $dateString, string $fieldName = 'date'): ?string
    {
        try {
            $date = new \DateTime($dateString);
            return null; // Válido
        } catch (\Exception $e) {
            Log::warning('Invalid date format', [
                'field' => $fieldName,
                'date' => $dateString,
                'error' => $e->getMessage()
            ]);
            return "Formato de fecha inválido en {$fieldName}: {$dateString}";
        }
    }

    /**
     * Formatear fecha al formato requerido por SuiteCRM v4.1 (Y-m-d H:i:s)
     *
     * @param string $dateString Fecha en cualquier formato
     * @return string Fecha en formato Y-m-d H:i:s
     */
    public function formatDateForSuiteCRM(string $dateString): string
    {
        try {
            // Intentar con formatos comunes primero
            $formats = [
                'Y-m-d H:i:s',      // Ya en formato SuiteCRM
                'Y-m-d\TH:i:s',     // ISO 8601 con segundos
                'Y-m-d\TH:i',       // ISO datetime-local
                'Y-m-d H:i',        // Datetime sin segundos
                'Y-m-d',            // Solo fecha
            ];

            $dateObj = null;
            foreach ($formats as $format) {
                $dateObj = \DateTime::createFromFormat($format, $dateString);
                if ($dateObj) {
                    break;
                }
            }

            // Si ningún formato coincide, intentar parseado automático
            if (!$dateObj) {
                $dateObj = new \DateTime($dateString);
            }

            $formatted = $dateObj->format('Y-m-d H:i:s');

            if ($formatted !== $dateString) {
                Log::debug('Date formatted for SuiteCRM', [
                    'original' => $dateString,
                    'formatted' => $formatted
                ]);
            }

            return $formatted;

        } catch (\Exception $e) {
            Log::error('Error formatting date for SuiteCRM', [
                'date' => $dateString,
                'error' => $e->getMessage()
            ]);

            // Devolver tal cual si no se puede parsear
            return $dateString;
        }
    }

    /**
     * Validar que parent (Case u Opportunity) exista
     *
     * Soporta búsqueda por ID local o sweetcrm_id para máxima compatibilidad
     *
     * @param string $parentType Tipo: 'Cases' o 'Opportunities'
     * @param string $parentId ID del parent (local o SuiteCRM)
     * @return Model|null Modelo encontrado o null
     */
    private function validateParent(string $parentType, string $parentId): ?Model
    {
        try {
            if ($parentType === 'Cases') {
                return CrmCase::where('id', $parentId)
                    ->orWhere('sweetcrm_id', $parentId)
                    ->first();
            } else {
                return Opportunity::where('id', $parentId)
                    ->orWhere('sweetcrm_id', $parentId)
                    ->first();
            }
        } catch (\Exception $e) {
            Log::error('Error validating parent', [
                'parent_type' => $parentType,
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validar dependencias circulares entre tareas
     *
     * Previene: Task A → Task B → Task C → Task A
     *
     * @param Task $childTask Tarea que se asignará como hijo
     * @param Task $parentTask Tarea que se asignará como padre
     * @return bool true si es válido, false si hay ciclo
     */
    public function validateNoCyclicalDependency(Task $childTask, Task $parentTask): bool
    {
        if ($childTask->id === $parentTask->id) {
            Log::warning('Cyclical dependency detected: self-reference', [
                'task_id' => $childTask->id
            ]);
            return false;
        }

        // Verificar que parentTask no es descendiente de childTask
        try {
            $ancestor = $parentTask;
            $maxDepth = 100; // Prevenir loops infinitos
            $depth = 0;

            while ($ancestor && $depth < $maxDepth) {
                if ($ancestor->id === $childTask->id) {
                    Log::warning('Cyclical dependency detected', [
                        'child_task_id' => $childTask->id,
                        'parent_task_id' => $parentTask->id,
                        'cycle_depth' => $depth
                    ]);
                    return false;
                }

                $ancestor = $ancestor->parentTask;
                $depth++;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error validating cyclical dependency', [
                'error' => $e->getMessage()
            ]);
            return true; // Permitir por defecto si hay error
        }
    }

    /**
     * Obtener lista de errores formateada para respuesta API
     *
     * @param array $errors Errores de validación
     * @return string Errores formateados
     */
    public function formatErrorMessage(array $errors): string
    {
        return implode('; ', $errors);
    }
}
