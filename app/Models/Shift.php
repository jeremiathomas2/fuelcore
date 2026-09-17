<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'cash_variance' => 'decimal:2',
        'opening_meter' => 'decimal:3',
        'closing_meter' => 'decimal:3',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUSES = ['open', 'closing', 'closed', 'cancelled'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function pump(): BelongsTo
    {
        return $this->belongsTo(Pump::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(ShiftReading::class);
    }

    public function reconcileCashFromSales(): void
    {
        $cashPaid = Payment::query()
            ->where('method', 'cash')
            ->where('status', 'paid')
            ->whereIn('transaction_id', $this->transactions()->pluck('fuel_transactions.id'))
            ->sum('amount');

        $expected = $this->opening_cash + (float) $cashPaid;
        $this->expected_cash = $expected;
        $this->cash_variance = $this->actual_cash !== null ? $this->actual_cash - $expected : null;
        $this->save();
    }
}