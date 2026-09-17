<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelProduct extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'min_stock' => 'decimal:3',
        'active' => 'boolean',
    ];

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(FuelProductPrice::class);
    }

    public function currentStockAt(Station $station): float
    {
        return (float) $station->tanks()
            ->where('fuel_product_id', $this->id)
            ->sum('current_volume');
    }
}