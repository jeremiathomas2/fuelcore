<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tank extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'capacity' => 'decimal:3',
        'current_volume' => 'decimal:3',
        'min_level' => 'decimal:3',
        'max_level' => 'decimal:3',
        'temperature' => 'decimal:2',
        'water_level' => 'decimal:3',
        'last_reading_at' => 'datetime',
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class, 'fuel_product_id');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(TankReading::class);
    }

    public function levelPercent(): float
    {
        return $this->capacity > 0 ? round($this->current_volume / $this->capacity * 100, 1) : 0;
    }

    public function evaluateStatus(): string
    {
        $pct = $this->levelPercent();

        return match (true) {
            $pct < 15 => 'critical',
            $pct < 35 => 'low',
            $pct < 50 => 'warning',
            default => 'normal',
        };
    }
}