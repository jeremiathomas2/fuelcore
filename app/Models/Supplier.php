<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'performance_rating' => 'integer',
    ];

    public const STATUSES = ['active', 'inactive'];

    public function deliveries(): HasMany
    {
        return $this->hasMany(FuelDelivery::class);
    }
}