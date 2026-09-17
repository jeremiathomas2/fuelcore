<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\User;
use App\Support\UrlId;
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
     *
     * The requested value is an encrypted URL token (see UrlId); a raw numeric
     * id is still accepted so internal callers/tests keep working.
     */
    protected function stationContext(User $user, int|string|null $requested = null): ?Station
    {
        $requestedId = is_numeric($requested) ? (int) $requested : UrlId::decode($requested);

        $visible = fn (int $id): bool => Station::query()->visibleTo($user)->whereKey($id)->exists();

        if ($requestedId && $visible($requestedId)) {
            return Station::find($requestedId);
        }

        $sessionId = (int) session('active_station');

        if ($sessionId && $visible($sessionId)) {
            return Station::find($sessionId);
        }

        if ($user->station_id && $visible($user->station_id)) {
            return Station::find($user->station_id);
        }

        return null;
    }

    protected function perPage(): int
    {
        return (int) request()->integer('per_page', 15) > 0 ? request()->integer('per_page', 15) : 15;
    }
}