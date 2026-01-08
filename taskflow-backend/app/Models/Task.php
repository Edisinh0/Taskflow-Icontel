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
        'created_by',
        'priority',
        'status',
        'is_milestone',
        'allow_attachments',
        'is_blocked',
        'depends_on_task_id',
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
        'notes',
        'last_updated_by',
        'case_id',
        'sweetcrm_id',
        'sweetcrm_synced_at',
        // SLA fields
        'sla_due_date',
        'sla_breached',
        'sla_breach_at',
        'sla_days_overdue',
        'sla_notified_assignee',
        'sla_escalated',
        'sla_notified_at',
        'sla_escalated_at',
        // Nuevos campos para SweetCRM
        'sweetcrm_parent_id',
        'sweetcrm_parent_type',
        'date_entered',
        'date_modified',
        'sequence',
        'created_by_id',
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
        // SLA casts
        'sla_due_date' => 'datetime',
        'sla_breached' => 'boolean',
        'sla_breach_at' => 'datetime',
        'sla_days_overdue' => 'integer',
        'sla_notified_assignee' => 'boolean',
        'sla_escalated' => 'boolean',
        'sla_notified_at' => 'datetime',
        'sla_escalated_at' => 'datetime',
        'sweetcrm_synced_at' => 'datetime',
        // Nuevos campos
        'date_entered' => 'datetime',
        'date_modified' => 'datetime',
        'sequence' => 'integer',
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
     * Relación: Usuario que creó la tarea
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * Relación: Una tarea puede pertenecer a un caso de CRM
     */
    public function crmCase(): BelongsTo
    {
        return $this->belongsTo(CrmCase::class, 'case_id');
    }

    /**
     * Relación: Una tarea puede pertenecer a una oportunidad
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CrmOpportunity::class, 'opportunity_id');
    }

    /**
     * Relación: Una tarea tiene muchos avances/updates
     */
    public function updates(): HasMany
    {
        return $this->hasMany(CaseUpdate::class, 'task_id')->orderBy('created_at', 'desc');
    }

    /**
     * Accessor para decodificar descripción HTML
     */
    public function getDescriptionAttribute($value)
    {
        return html_entity_decode(html_entity_decode($value ?? ''));
    }

    /**
     * Verificar y actualizar el estado del SLA
     */
    public function checkSlaStatus(): void
    {
        // Solo verificar si no está completada o cancelada
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return;
        }

        // Si no hay fecha de SLA definida, usar estimated_end_at
        if (!$this->sla_due_date && $this->estimated_end_at) {
            $this->sla_due_date = $this->estimated_end_at;
            $this->save();
        }

        // Si no hay fecha de SLA, no hacer nada
        if (!$this->sla_due_date) {
            return;
        }

        $now = now();
        $dueDate = $this->sla_due_date;

        // Verificar si se ha superado el SLA
        if ($now->isAfter($dueDate)) {
            // Calcular días de retraso (positivo)
            $daysOverdue = (int) $dueDate->diffInDays($now);

            if (!$this->sla_breached) {
                $this->sla_breached = true;
                $this->sla_breach_at = $now;
            }

            $this->sla_days_overdue = $daysOverdue;
            $this->save();
        }
    }

    /**
     * Verificar si la tarea está retrasada
     */
    public function isOverdue(): bool
    {
        if (!$this->sla_due_date) {
            return false;
        }

        return now()->isAfter($this->sla_due_date) &&
               !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Obtener el supervisor/PM del flujo para escalamiento
     */
    public function getSupervisor()
    {
        // Obtener el creador del flujo como supervisor
        if ($this->flow && $this->flow->created_by) {
            return User::find($this->flow->created_by);
        }

        // Alternativamente, buscar usuarios con rol de admin o project_manager
        return User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'project_manager']);
        })->first();
    }

    /**
     * Scope para tareas con SLA vencido
     */
    public function scopeSlaBreach($query)
    {
        return $query->where('sla_breached', true)
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope para tareas que necesitan notificación (+1 día)
     */
    public function scopeNeedsAssigneeNotification($query)
    {
        return $query->where('sla_breached', true)
                    ->where('sla_days_overdue', '>=', 1)
                    ->where('sla_notified_assignee', false)
                    ->whereNotNull('assignee_id')
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope para tareas que necesitan escalamiento (+2 días)
     */
    public function scopeNeedsEscalation($query)
    {
        return $query->where('sla_breached', true)
                    ->where('sla_days_overdue', '>=', 2)
                    ->where('sla_escalated', false)
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }
}