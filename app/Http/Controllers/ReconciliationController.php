<?php

namespace App\Http\Controllers;

use App\Models\Reconciliation;
use App\Models\Shift;
use App\Models\Station;
use App\Services\ReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $reconciliations = Reconciliation::query()
            ->with(['station', 'shift.employee', 'reconciledBy'])
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('reconciliations.index', [
            'reconciliations' => $reconciliations,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
            'openShifts' => Shift::where('status', 'open')
                ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
                ->with('station', 'employee')
                ->get(),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $station = Station::findOrFail($data['station_id']);
        $this->authorize('access-station', $station);

        try {
            $recon = app(ReconciliationService::class)->run($station, $data, $user);

            $message = $recon->variance_litres == 0
                ? 'Reconciliation run — no variance. '
                : 'Reconciliation run — variance ' . number_format((float) $recon->variance_litres, 2) . ' L (' . number_format((float) $recon->variance_pct, 2) . '%). ';

            return redirect()->route('reconciliations.index')->with('success', $message . 'Expected closing ' . number_format((float) $recon->expected_closing, 2) . ' L.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}