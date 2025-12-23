<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    use \OwenIt\Auditing\Auditable;
     /**
     * Relación: Un usuario puede crear muchas plantillas
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, 'created_by');
    }

    /**
     * Relación: Un usuario puede crear muchos flujos
     */
    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class, 'created_by');
    }

    /**
     * Relación: Un usuario puede tener muchas tareas asignadas
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'sweetcrm_id',
        'sweetcrm_user_type',
        'sweetcrm_synced_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sweetcrm_synced_at' => 'datetime',
        ];
    }

    /**
     * Verificar si el usuario está sincronizado con SweetCRM
     */
    public function isSyncedWithSweetCrm(): bool
    {
        return !is_null($this->sweetcrm_id);
    }

    /**
     * Verificar si la sincronización está desactualizada
     */
    public function needsSweetCrmSync(): bool
    {
        if (!$this->isSyncedWithSweetCrm()) {
            return false;
        }

        $syncInterval = config('services.sweetcrm.sync_interval', 3600);

        return is_null($this->sweetcrm_synced_at) ||
               $this->sweetcrm_synced_at->addSeconds($syncInterval)->isPast();
    }
}
