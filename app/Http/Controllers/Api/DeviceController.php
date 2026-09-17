<?php

namespace App\Http\Controllers\Api;

use App\Models\Station;
use App\Models\StationDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DeviceController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_code' => 'required|string',
            'device_code' => 'required|string',
            'device_type' => 'sometimes|string|in:fcc,pos,atg,gateway,other',
            'name' => 'sometimes|string',
            'status' => 'sometimes|string|in:online,offline,error',
            'uptime' => 'nullable|integer',
        ]);

        $station = Station::where('code', $data['station_code'])->firstOrFail();

        $device = StationDevice::updateOrCreate(
            [
                'station_id' => $station->id,
                'device_code' => $data['device_code'],
            ],
            [
                'device_type' => $request->input('device_type', 'fcc'),
                'name' => $request->input('name', 'Station Controller'),
                'last_heartbeat_at' => now(),
                'status' => $data['status'] ?? 'online',
                'config' => $request->has('uptime')
                    ? ['uptime' => $data['uptime']]
                    : null,
            ],
        );

        return response()->json(['status' => 'ok', 'device_id' => $device->id]);
    }
}