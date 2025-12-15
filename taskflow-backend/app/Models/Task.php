<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Task extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'title',
        'description',
        'flow_id',
        'parent_task_id',
        'assignee_id',
        'priority',
        'status',
        'is_milestone',
        'allow_attachments', // <-- AGREGAR
        'is_blocked',           // <-- AGREGAR
        'depends_on_task_id',   // <-- AGREGAR
        'depends_on_milestone_id',
        'milestone_auto_complete',
        'milestone_requires_validation',
        'milestone_validated_by',
        'milestone_target_date',
        'order',
        'estimated_start_at',
        'estimated_end_at',
        'actual_start_at',
        'actual_end_at',
        'progress',
        'blocked_reason',
        'last_updated_by', // Nuevo
    ];

    protected $casts = [
        'is_milestone' => 'boolean',
        'allow_attachments' => 'boolean',
        'milestone_auto_complete' => 'boolean',
        'milestone_requires_validation' => 'boolean',
        'milestone_target_date' => 'datetime',
        'estimated_start_at' => 'datetime',
        'estimated_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'progress' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_blocked' => 'boolean',
    ];

    /**
     * Relación: Una tarea pertenece a un flujo
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    /**
     * Relación: Una tarea puede tener una tarea padre
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Relación: Una tarea puede tener muchas subtareas
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Relación: Una tarea tiene un responsable (assignee)
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Relación: Usuario que validó el milestone
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'milestone_validated_by');
    }

    /**
     * Relación: Dependencias - Tareas de las que esta depende
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    /**
     * Relación: Tareas que dependen de esta
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'depends_on_task_id');
    }

    /**
     * Verificar si la tarea está bloqueada por dependencias
     */
    public function isBlocked(): bool
    {
        // Si ya está completada, no está bloqueada
        if ($this->status === 'completed') {
            return false;
        }

        // Verificar si alguna dependencia no está completada
        foreach ($this->dependencies as $dependency) {
            // Usar la relación si está cargada, o cargarla (evita find() manual que es menos eficiente)
            $dependsOnTask = $dependency->dependsOnTask; 
            if ($dependsOnTask && $dependsOnTask->status !== 'completed') {
                return true;
            }
        }

        return false;
    }

    /**
     * Calcular el progreso basado en subtareas
     */
    public function calculateProgress(): int
    {
        $subtasks = $this->subtasks;
        
        if ($subtasks->isEmpty()) {
            return $this->progress;
        }

        $totalProgress = $subtasks->sum('progress');
        $count = $subtasks->count();

        return $count > 0 ? (int) ($totalProgress / $count) : 0;
    }

    /**
     * Relación: Tarea de la que esta tarea depende (dependencia de flujo)
     */
    public function dependsOnTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }

    /**
     * Relación: Milestone del que esta tarea depende
     */
    public function dependsOnMilestone(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_milestone_id');
    }

    /**
     * Relación inversa: Tareas que dependen de esta tarea
     */
    public function dependentTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'depends_on_task_id');
    }

    /**
     * Relación inversa: Tareas que dependen de este milestone
     */
    public function dependentOnMilestone(): HasMany
    {
        return $this->hasMany(Task::class, 'depends_on_milestone_id');
    }

    /**
     * Relación: Archivos adjuntos
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}