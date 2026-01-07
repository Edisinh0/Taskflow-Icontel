<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseUpdateAttachment extends Model
{
    protected $fillable = [
        'case_update_id',
        'user_id',
        'name',
        'file_path',
        'file_type',
        'file_size'
    ];

    public function update(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CaseUpdate::class, 'case_update_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
