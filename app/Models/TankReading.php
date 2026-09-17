<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankReading extends Model
{
    protected $guarded = [];

    protected $casts = [
        'reading_litres' => 'decimal:3',
        'temperature' => 'decimal:2',
        'water_level' => 'decimal:3',
        'leak_detected' => 'boolean',
        'reading_at' => 'datetime',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}