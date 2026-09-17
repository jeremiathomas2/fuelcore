<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $guarded = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function syncPermissions(iterable $permissionSlugs): void
    {
        $ids = Permission::whereIn('slug', $permissionSlugs)->pluck('id');
        $this->permissions()->sync($ids);
    }
}