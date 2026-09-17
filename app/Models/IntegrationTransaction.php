<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public const STATUSES = ['received', 'processed', 'duplicate', 'failed'];
    public const SYNC_STATUSES = ['pending', 'success', 'failed'];
}