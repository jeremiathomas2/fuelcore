<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelDeliveryItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'ordered_qty' => 'decimal:3',
        'delivered_qty' => 'decimal:3',
        'expected_qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(FuelDelivery::class, 'fuel_delivery_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class, 'fuel_product_id');
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}