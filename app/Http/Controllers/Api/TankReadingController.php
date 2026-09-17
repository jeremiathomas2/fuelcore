<?php

namespace App\Http\Controllers\Api;

use App\Models\Station;
use App\Models\Tank;
use App\Models\TankReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TankReadingController extends Controller
{
    public function stationTanks(Station $station): JsonResponse
    {
        return response()->json($station->tanks()->with(['fuelProduct', 'readings'])->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_code' => 'required|string',
            'tank_number' => 'required|string',
            'reading_litres' => 'required|numeric',
            'temperature' => 'nullable|numeric',
            'water_level' => 'nullable|numeric',
            'leak_detected' => 'sometimes|boolean',
            'reading_at' => 'sometimes|date',
        ]);

        $station = Station::where('code', $data['station_code'])->firstOrFail();
        $tank = Tank::where('station_id', $station->id)->where('tank_number', $data['tank_number'])->firstOrFail();

        // The tank volume is our source of truth for station stock — update it.
        $tank->update([
            'current_volume' => $data['reading_litres'],
            'temperature' => $data['temperature'] ?? $tank->temperature,
            'water_level' => $data['water_level'] ?? $tank->water_level,
            'leak_status' => ! empty($data['leak_detected']) ? 'yes' : 'no',
            'status' => $tank->evaluateStatus(),
            'last_reading_at' => now(),
        ]);

        $reading = TankReading::create([
            'tank_id' => $tank->id,
            'station_id' => $station->id,
            'reading_litres' => $data['reading_litres'],
            'temperature' => $data['temperature'] ?? null,
            'water_level' => $data['water_level'] ?? 0,
            'leak_detected' => $data['leak_detected'] ?? false,
            'source' => 'integration',
            'reading_at' => $data['reading_at'] ?? now(),
        ]);

        return response()->json(['status' => 'ok', 'reading_id' => $reading->id], 201);
    }
}