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
    ];

    protected $casts = [
        'sweetcrm_synced_at' => 'datetime',
        'sweetcrm_created_at' => 'datetime',
        'closure_requested_at' => 'datetime',
        'closure_requested' => 'boolean',
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
}
