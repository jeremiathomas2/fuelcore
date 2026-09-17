<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
    ];

    public const TYPES = ['individual', 'corporate', 'fleet', 'government', 'other'];

    public static function generateCustomerNumber(): string
    {
        do {
            $number = 'CUS-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('customer_number', $number)->exists());

        return $number;
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicle::class);
    }

    public function fleetAccounts(): HasMany
    {
        return $this->hasMany(FleetAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FuelTransaction::class);
    }
}