<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Scope an Eloquent query to the stations this user may access.
     */
    protected function visibleStations(User $user): Builder
    {
        return Station::query()->visibleTo($user);
    }

    /**
     * Resolve the station context for the current user / request.
     */
    protected function stationContext(User $user, ?int $requestedId = null): ?Station
    {
        if ($requestedId && Station::query()->visibleTo($user)->whereKey($requestedId)->exists()) {
            return Station::find($requestedId);
        }

        if ($user->station_id && Station::query()->visibleTo($user)->whereKey($user->station_id)->exists()) {
            return Station::find($user->station_id);
        }

        return null;
    }

    protected function perPage(): int
    {
        return (int) request()->integer('per_page', 15) > 0 ? request()->integer('per_page', 15) : 15;
    }
}