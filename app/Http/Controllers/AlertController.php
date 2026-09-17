<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $alerts = Alert::query()
            ->with('station')
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id')->push(null))
            ->when($request->filled('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->when($request->boolean('resolved'), fn ($q) => $q->whereNotNull('resolved_at'), fn ($q) => $q->unresolved())
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        $counts = Alert::query()
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id')->push(null))
            ->unresolved()
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity');

        return view('alerts.index', [
            'alerts' => $alerts,
            'counts' => $counts,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
        ]);
    }

    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        AlertService::resolve($alert, $request->user());

        return redirect()->route('alerts.index')->with('success', 'Alert marked as resolved.');
    }
}