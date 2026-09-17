<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FuelTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meter_start' => 'decimal:3',
        'meter_end' => 'decimal:3',
        'litres' => 'decimal:3',
        'price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'transacted_at' => 'datetime',
        'received_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'dispensing', 'completed', 'cancelled', 'voided', 'refunded'];
    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'cancelled', 'refunded'];

    protected static function booted(): void
    {
        static::creating(function (FuelTransaction $t) {
            if (empty($t->uuid)) {
                $t->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($t->transaction_number)) {
                $t->transaction_number = 'FS-' . now()->format('ymdH') . str_pad((string) (self::max('id') + 1), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function pump(): BelongsTo
    {
        return $this->belongsTo(Pump::class);
    }

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function fuelProduct(): BelongsTo
    {
        return $this->belongsTo(FuelProduct::class, 'fuel_product_id');
    }

    public function attendant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fleetAccount(): BelongsTo
    {
        return $this->belongsTo(FleetAccount::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FuelTransactionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'transaction_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'transaction_id');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isSuperAdmin() || $user->hasAnyRole(['head_office_admin', 'accountant', 'auditor'])) {
            return $query;
        }

        return $query->whereIn('station_id', $user->stations()->pluck('station_id'));
    }
}