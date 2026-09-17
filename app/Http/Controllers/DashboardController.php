<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $selectedStation = $this->stationContext($user, $request->query('station'));

        $data = $this->dashboardService->get(['user' => $user, 'station' => $selectedStation]);

        $stations = $this->visibleStations($user)->orderBy('code')->get();

        return view('dashboard', array_merge($data, [
            'stations' => $stations,
            'selectedStation' => $selectedStation,
        ]));
    }
}