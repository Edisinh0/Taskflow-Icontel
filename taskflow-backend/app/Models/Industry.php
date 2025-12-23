<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the clients in this industry
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get the templates for this industry
     */
    public function templates()
    {
        return $this->belongsToMany(Template::class, 'template_industries');
    }
}
