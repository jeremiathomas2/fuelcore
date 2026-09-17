<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nozzle extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'meter_start' => 'decimal:3',
        'meter_current' => 'decimal:3',
        'total_litres' => 'decimal:3',
        'last_transaction_at' => 'datetime',
    ];

    public const STATUSES = ['idle', 'dispensing', 'completed', 'offline', 'error', 'maintenance'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function pump(): BelongsTo
    {
        return $this->belongsTo(Pump::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class, 'fuel_product_id');
    }
}