<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;

class Template extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    /**
     * Los atributos que se pueden asignar masivamente
     */
    protected $fillable = [
        'name',
        'description',
        'version',
        'is_active',
        'created_by',
        'config',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array', // JSON se convierte automáticamente a array
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación: Una plantilla pertenece a un usuario (creador)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación: Una plantilla tiene muchos flujos
     */
    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class);
    }

    /**
     * Relación: Una plantilla puede servir a múltiples industrias
     */
    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class, 'template_industries');
    }

    /**
     * Scope para filtrar plantillas por industria
     */
    public function scopeForIndustry($query, $industryId)
    {
        return $query->whereHas('industries', function ($q) use ($industryId) {
            $q->where('industry_id', $industryId);
        });
    }
}