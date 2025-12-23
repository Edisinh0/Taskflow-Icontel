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
        'sweetcrm_id',
        'sweetcrm_account_id',
        'sweetcrm_assigned_user_id',
        'sweetcrm_synced_at',
    ];

    protected $casts = [
        'sweetcrm_synced_at' => 'datetime',
    ];

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
     * Relación: Usuario asignado en SweetCRM
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sweetcrm_assigned_user_id', 'sweetcrm_id');
    }
}
