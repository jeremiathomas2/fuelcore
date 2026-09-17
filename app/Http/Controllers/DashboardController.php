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

        $data = $this->dashboardService->get(['user' => $user]);

        $stations = $this->visibleStations($user)->orderBy('code')->get();
        $selectedStation = $this->stationContext($user, $request->integer('station'));

        return view('dashboard', array_merge($data, [
            'stations' => $stations,
            'selectedStation' => $selectedStation,
        ]));
    }
}