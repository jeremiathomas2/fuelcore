<?php

namespace App\Http\Controllers\Api;

use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class StationController extends Controller
{
    public function index(): JsonResponse
    {
        $stations = Station::select('id', 'code', 'name', 'region', 'status')
            ->orderBy('code')
            ->get();

        return response()->json($stations);
    }

    public function show(Station $station): JsonResponse
    {
        return response()->json($station->load(['pumps', 'nozzles.fuelProduct', 'tanks']));
    }
}