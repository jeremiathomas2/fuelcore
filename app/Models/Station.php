<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'opening_date' => 'date',
    ];

    public const STATUSES = ['online', 'warning', 'maintenance', 'offline'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'station_user')
            ->withPivot('station_role', 'is_assigned')
            ->withTimestamps();
    }

    public function pumps(): HasMany
    {
        return $this->hasMany(Pump::class);
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(FuelDelivery::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(StationDevice::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->hasAnyRole(['head_office_admin', 'accountant', 'auditor', 'inventory_officer'])) {
            return $query;
        }

        return $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn ($q, $s) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
                ->orWhere('region', 'like', "%{$s}%")
                ->orWhere('district', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['region'] ?? null, fn ($q, $s) => $q->where('region', $s));
    }
}