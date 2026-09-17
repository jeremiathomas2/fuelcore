<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public const METHODS = ['cash', 'mobile_money', 'card', 'bank_transfer', 'fleet_account', 'credit'];
    public const STATUSES = ['pending', 'paid', 'failed', 'cancelled', 'refunded'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FuelTransaction::class, 'transaction_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}