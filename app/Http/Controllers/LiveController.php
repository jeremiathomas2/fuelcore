<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Real-time operations monitor (pumps, nozzles, devices).
 */
class LiveController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $stationIds = $this->visibleStations($user)->pluck('id');

        $data = [
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
            'selectedStation' => $this->stationContext($user, $request->integer('station')),
        ];

        return view('live.index', $data);
    }

    public function data(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $stationIds = $this->visibleStations($user)->pluck('id');

        $query = \App\Models\Nozzle::query()
            ->whereIn('station_id', $stationIds)
            ->with(['station', 'pump', 'fuelProduct']);

        if ($request->integer('station')) {
            $query->where('station_id', $request->integer('station'));
        }

        $nozzles = $query->get()->map(fn ($n) => [
            'id' => $n->id,
            'number' => $n->nozzle_number,
            'station' => "{$n->station?->code}",
            'pump' => $n->pump?->pump_number,
            'fuel' => $n->fuelProduct?->name,
            'status' => $n->status,
            'price' => $n->fuelProduct?->price,
            'litres' => (float) $n->last_transaction_at ? $n->total_litres : 0,
        ]);

        return response()->json([
            'now' => now()->format('H:i:s'),
            'nozzles' => $nozzles,
            'pumps' => \App\Models\Pump::whereIn('station_id', $stationIds)->get([
                'id', 'pump_number', 'status', 'station_id',
            ]),
        ]);
    }
}