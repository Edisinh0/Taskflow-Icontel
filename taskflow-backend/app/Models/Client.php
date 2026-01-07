<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use OwenIt\Auditing\Contracts\Auditable;

class Client extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'industry',
        'industry_id',
        'account_type',
        'sweetcrm_id',
        'sweetcrm_assigned_user_id',
        'sweetcrm_synced_at',
        'status',
        'contact_email',
        'contact_phone',
        'website',
        'notes',
    ];

    protected $casts = [
        'sweetcrm_synced_at' => 'datetime',
    ];

    /**
     * Get the industry this client belongs to
     */
    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Get the flows for this client
     */
    public function flows()
    {
        return $this->hasMany(Flow::class);
    }

    /**
     * Get the attachments for this client
     */
    public function attachments()
    {
        return $this->hasMany(ClientAttachment::class);
    }

    /**
     * Get the contacts for this client
     */
    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    /**
     * Get the user this client is assigned to in SweetCRM
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'sweetcrm_assigned_user_id', 'sweetcrm_id');
    }
}
