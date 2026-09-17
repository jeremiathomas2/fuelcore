<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pump extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'installation_date' => 'date',
        'last_communication_at' => 'datetime',
    ];

    public const STATUSES = ['online', 'offline', 'maintenance', 'error'];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }
}