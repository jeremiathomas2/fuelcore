<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'employee_number', 'password', 'status', 'station_id', 'locale', 'email_verified_at', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function stations(): BelongsToMany
    {
        return $this->belongsToMany(Station::class, 'station_user')
            ->withPivot('station_role', 'is_assigned')
            ->withTimestamps();
    }

    public function hasRole(string|array $slug): bool
    {
        $slugs = (array) $slug;

        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    public function hasAnyRole(array $slugs): bool
    {
        return $this->hasRole($slugs);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function permissionSlugs(): array
    {
        if ($this->isSuperAdmin()) {
            return config('permissions.permissions');
        }

        static $cache = [];

        return $cache[$this->id] ??= $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermissionTo(string $permission): bool
    {
        return in_array($permission, $this->permissionSlugs(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->hasPermissionTo($p)) {
                return true;
            }
        }

        return false;
    }

    public function primaryRole(): ?Role
    {
        return $this->roles()->orderBy('id')->first();
    }

    public function roleLabel(): string
    {
        return $this->primaryRole()?->name ?? 'No Role';
    }

    public function getAvatarTextAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));

        return collect($words)->take(2)->map(fn ($w) => strtoupper(mb_substr($w, 0, 1)))->join('');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(FuelTransaction::class, 'attendant_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'employee_id');
    }
}