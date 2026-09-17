<?php

namespace App\Http\Controllers\Api;

use App\Models\Pump;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PumpController extends Controller
{
    public function index(Station $station): JsonResponse
    {
        return response()->json($station->pumps()->with('nozzles')->get());
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_code' => 'required|string',
            'pump_number' => 'required|string',
            'status' => 'required|in:online,offline,maintenance,error',
        ]);

        $station = Station::where('code', $data['station_code'])->firstOrFail();
        $pump = Pump::where('station_id', $station->id)->where('pump_number', $data['pump_number'])->firstOrFail();
        $pump->update([
            'status' => $data['status'],
            'last_communication_at' => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}