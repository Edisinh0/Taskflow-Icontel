<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'user_id',
        'content',
        'type',
        'task_id',
    ];

    /**
     * Tipos de actualización disponibles
     */
    const TYPE_UPDATE = 'update';
    const TYPE_CLOSURE_REQUEST = 'closure_request';
    const TYPE_CLOSURE_APPROVED = 'closure_approved';
    const TYPE_CLOSURE_REJECTED = 'closure_rejected';

    /**
     * Relación: Pertenece a un caso
     */
    public function crmCase(): BelongsTo
    {
        return $this->belongsTo(CrmCase::class, 'case_id');
    }

    /**
     * Relación: Usuario que creó el avance
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Tarea relacionada (opcional)
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Relación: Un avance puede tener muchos archivos adjuntos
     */
    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CaseUpdateAttachment::class, 'case_update_id');
    }

    /**
     * Scope para ordenar por más reciente
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Obtener el ícono según el tipo de actualización
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CLOSURE_REQUEST => 'clock',
            self::TYPE_CLOSURE_APPROVED => 'check-circle',
            self::TYPE_CLOSURE_REJECTED => 'x-circle',
            default => 'message-circle',
        };
    }

    /**
     * Obtener el color según el tipo de actualización
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CLOSURE_REQUEST => 'amber',
            self::TYPE_CLOSURE_APPROVED => 'emerald',
            self::TYPE_CLOSURE_REJECTED => 'rose',
            default => 'blue',
        };
    }
}
