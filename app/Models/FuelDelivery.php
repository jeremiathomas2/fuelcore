<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelDelivery extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'ordered_qty' => 'decimal:3',
        'delivered_qty' => 'decimal:3',
        'expected_qty' => 'decimal:3',
        'variance' => 'decimal:3',
        'delivery_date' => 'datetime',
        'meter_reading_start' => 'decimal:3',
        'meter_reading_end' => 'decimal:3',
        'completed_at' => 'datetime',
    ];

    public const STATUSES = ['scheduled', 'in_transit', 'receiving', 'completed', 'reconciled', 'disputed', 'cancelled'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FuelDeliveryItem::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}