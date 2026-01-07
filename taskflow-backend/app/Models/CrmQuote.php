<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmQuote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quote_number',
        'subject',
        'sweetcrm_id',
        'status',
        'total_amount',
        'opportunity_id',
        'client_id',
        'description',
        'sweetcrm_synced_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'sweetcrm_synced_at' => 'datetime',
    ];

    public function opportunity()
    {
        return $this->belongsTo(CrmOpportunity::class, 'opportunity_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Accessor para decodificar descripción HTML
     */
    public function getDescriptionAttribute($value)
    {
        return html_entity_decode(html_entity_decode($value ?? ''));
    }
}
