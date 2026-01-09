<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_number',
        'subject',
        'description',
        'status',
        'priority',
        'type',
        'area',
        'client_id',
        'created_by',
        'sweetcrm_id',
        'sweetcrm_account_id',
        'sweetcrm_assigned_user_id',
        'sweetcrm_synced_at',
        'sweetcrm_created_at',
        // Nuevos campos
        'original_creator_id',
        'original_creator_name',
        'assigned_user_name',
        'closure_requested',
        'closure_requested_at',
        'closure_requested_by',
        'closure_rejection_reason',
        // Campos adicionales
        'internal_notes',
        'priority_score',
        'last_activity_at',
        'account_name',
        'account_number',
        'sla_status',
        'sla_due_date',
        // Workflow fields
        'workflow_status',
        'original_sales_user_id',
        'pending_validation_at',
        'validation_initiated_by_id',
        'approved_at',
        'approved_by_id',
        'validation_rejection_reason',
        'rejected_at',
        'rejected_by_id',
    ];

    protected $casts = [
        'sweetcrm_synced_at' => 'datetime',
        'sweetcrm_created_at' => 'datetime',
        'closure_requested_at' => 'datetime',
        'closure_requested' => 'boolean',
        'last_activity_at' => 'datetime',
        'sla_due_date' => 'datetime',
        // Workflow casts
        'pending_validation_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Accessor para decodificar entidades HTML en la descripción automáticamente
     */
    public function getDescriptionAttribute($value)
    {
        return html_entity_decode(html_entity_decode($value ?? ''));
    }

    /**
     * Relación: Un caso pertenece a un cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación: Un caso tiene muchas tareas
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'case_id');
    }

    /**
     * Relación: Un caso tiene muchos avances/updates
     */
    public function updates(): HasMany
    {
        return $this->hasMany(CaseUpdate::class, 'case_id')->orderBy('created_at', 'desc');
    }

    /**
     * Relación: Un caso tiene una solicitud de cierre (la más reciente)
     */
    public function latestClosureRequest(): HasMany
    {
        return $this->hasMany(CaseClosureRequest::class, 'case_id')->latest('created_at');
    }

    /**
     * Relación: Usuario asignado en SweetCRM
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sweetcrm_assigned_user_id', 'sweetcrm_id');
    }

    /**
     * Relación: Usuario que solicitó el cierre
     */
    public function closureRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closure_requested_by');
    }

    /**
     * Relación: Usuario que creó el caso (local)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación: Usuario que solicitó el cierre (nuevo sistema)
     */
    public function closureRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closure_requested_by_id');
    }

    /**
     * Relación: Usuario que aprobó el cierre
     */
    public function closureApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closure_approved_by_id');
    }

    /**
     * Relación: Usuario original de ventas
     */
    public function originalSalesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_sales_user_id');
    }

    /**
     * Relación: Usuario que inició la validación
     */
    public function validationInitiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validation_initiated_by_id');
    }

    /**
     * Relación: Usuario que aprobó la validación
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Relación: Usuario que rechazó la validación
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    /**
     * Relación: Historial de workflow del caso
     */
    public function workflowHistory(): HasMany
    {
        return $this->hasMany(CaseWorkflowHistory::class, 'case_id')->orderBy('created_at', 'desc');
    }
}
