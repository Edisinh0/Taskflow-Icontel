<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmOpportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sweetcrm_id',
        'sales_stage',
        'amount',
        'currency',
        'expected_closed_date',
        'client_id',
        'sweetcrm_assigned_user_id',
        'description',
        'sweetcrm_synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expected_closed_date' => 'date',
        'sweetcrm_synced_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function quotes()
    {
        return $this->hasMany(CrmQuote::class, 'opportunity_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'opportunity_id');
    }

    /**
     * Accessor para decodificar descripción HTML
     */
    public function getDescriptionAttribute($value)
    {
        return html_entity_decode(html_entity_decode($value ?? ''));
    }
}
