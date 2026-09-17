<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationDevice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'config' => 'array',
    ];

    public const TYPES = ['fcc', 'pos', 'atg', 'gateway', 'other'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}