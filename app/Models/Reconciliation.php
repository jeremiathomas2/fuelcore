<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reconciliation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opening_stock' => 'decimal:3',
        'deliveries_qty' => 'decimal:3',
        'adjustments_qty' => 'decimal:3',
        'sales_qty' => 'decimal:3',
        'expected_closing' => 'decimal:3',
        'actual_reading' => 'decimal:3',
        'variance_litres' => 'decimal:3',
        'variance_pct' => 'decimal:3',
        'variance_value' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public const STATUSES = ['draft', 'reconciled', 'disputed'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}