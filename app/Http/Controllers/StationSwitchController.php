<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StationSwitchController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'station_id' => ['nullable', 'integer'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ]);

        $user = $request->user();
        $stationId = $request->integer('station_id');

        if ($stationId) {
            $station = $this->visibleStations($user)->whereKey($stationId)->first();
            abort_if($station === null, 403);

            $request->session()->put('active_station', $station->id);
            $label = $station->name;
        } else {
            $request->session()->forget('active_station');
            $label = 'All Stations';
        }

        $target = $this->localRedirect($request, (string) $request->input('redirect', ''));

        return ($target ? redirect()->to($target) : redirect()->back())
            ->with('success', "Switched to {$label}.");
    }

    /**
     * Only allow redirects back to this application, never to an external host.
     */
    private function localRedirect(Request $request, string $redirect): ?string
    {
        if ($redirect === '') {
            return null;
        }

        if (str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return $redirect;
        }

        $scheme = parse_url($redirect, PHP_URL_SCHEME);
        $host = parse_url($redirect, PHP_URL_HOST);

        if (! $host || ! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $port = parse_url($redirect, PHP_URL_PORT);
        $hostWithPort = $port ? "{$host}:{$port}" : $host;

        return $hostWithPort === $request->getHttpHost() ? $redirect : null;
    }
}
