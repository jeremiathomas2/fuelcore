<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public const SEVERITIES = ['critical', 'warning', 'info'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function markResolved(?User $user = null): void
    {
        $this->resolved_at = now();
        $this->resolved_by = $user?->id;
        $this->save();
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }
}