<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetAccount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'fuel_limit_litres' => 'decimal:3',
        'credit_limit' => 'decimal:2',
        'monthly_statement' => 'boolean',
    ];

    public static function generateAccountNumber(): string
    {
        do {
            $number = 'FLT-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('account_number', $number)->exists());

        return $number;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(FleetVehicle::class);
    }

    public function authorizedProducts(): BelongsToMany
    {
        return $this->belongsToMany(FuelProduct::class, 'fleet_account_fuel_product');
    }
}