<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseWorkflowHistory extends Model
{
    use HasFactory;

    protected $table = 'case_workflow_history';

    protected $fillable = [
        'case_id',
        'from_status',
        'to_status',
        'action',
        'performed_by_id',
        'notes',
        'reason',
        'sweetcrm_sync_status',
        'sweetcrm_sync_response',
        'sweetcrm_synced_at',
    ];

    protected $casts = [
        'sweetcrm_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: El historial pertenece a un caso
     */
    public function crmCase(): BelongsTo
    {
        return $this->belongsTo(CrmCase::class, 'case_id');
    }

    /**
     * Relación: El usuario que realizó la acción
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    /**
     * Scope: Obtener historial pendiente de sincronización
     */
    public function scopePendingSync($query)
    {
        return $query->where('sweetcrm_sync_status', 'pending');
    }

    /**
     * Scope: Obtener historial sincronizado
     */
    public function scopeSynced($query)
    {
        return $query->where('sweetcrm_sync_status', 'synced');
    }

    /**
     * Scope: Obtener historial con errores de sincronización
     */
    public function scopeSyncFailed($query)
    {
        return $query->where('sweetcrm_sync_status', 'failed');
    }

    /**
     * Scope: Obtener historial por acción
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
