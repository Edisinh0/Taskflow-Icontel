<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'flow_id',
        'type',
        'title',
        'message',
        'priority',
        'is_read',
        'read_at',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    /**
     * Marcar como leída
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope: Solo no leídas
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Urgentes
     */
    public function scopeUrgent($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }
}