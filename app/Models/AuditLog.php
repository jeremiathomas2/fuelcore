<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(array $data): self
    {
        $data['ip_address'] = $data['ip_address'] ?? request()->ip();
        $data['user_agent'] = $data['user_agent'] ?? substr(request()->userAgent() ?? '', 0, 255);
        if (empty($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }

        return static::create($data);
    }
}