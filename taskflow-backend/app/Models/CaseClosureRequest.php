<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseClosureRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'requested_by_user_id',
        'assigned_to_user_id',
        'status',
        'reason',
        'completion_percentage',
        'rejection_reason',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'completion_percentage' => 'integer',
    ];

    /**
     * Relación: Solicitud pertenece a un caso
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CrmCase::class, 'case_id');
    }

    /**
     * Relación: Solicitud fue creada por un usuario
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Relación: Solicitud fue asignada a un usuario (jefe de área)
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Relación: Solicitud fue revisada por un usuario
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Scope: Obtener solicitudes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Obtener solicitudes aprobadas
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Obtener solicitudes rechazadas
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: Obtener solicitudes asignadas a un usuario
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    /**
     * Verificar si la solicitud está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar si la solicitud fue aprobada
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verificar si la solicitud fue rechazada
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
