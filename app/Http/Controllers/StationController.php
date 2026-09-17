<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stations = $this->visibleStations($user)
            ->with(['pumps', 'tanks'])
            ->filter($request->only(['search', 'status', 'region']))
            ->withCount(['pumps', 'nozzles', 'tanks', 'transactions'])
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('stations.index', [
            'stations' => $stations,
            'regions' => Station::whereNotNull('region')->distinct()->orderBy('region')->pluck('region'),
        ]);
    }

    public function create(): View
    {
        return view('stations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $station = Station::create($data);

        AuditLog::record([
            'action' => 'create',
            'module' => 'stations',
            'record_id' => $station->id,
            'description' => "Station created: {$station->name} ({$station->code})",
            'new_values' => $station->only(['code', 'name', 'region', 'status']),
        ]);

        return redirect()->route('stations.show', $station)->with('success', 'Station created.');
    }

    public function show(Request $request, Station $station): View
    {
        $this->authorize('access-station', $station);

        $data = app(\App\Services\DashboardService::class)->station($station);

        return view('stations.show', $data);
    }

    public function edit(Station $station): View
    {
        $this->authorize('access-station', $station);

        return view('stations.edit', ['station' => $station]);
    }

    public function update(Request $request, Station $station): RedirectResponse
    {
        $this->authorize('access-station', $station);

        $data = $request->validate($this->rules($station->id));
        $station->update($data);

        AuditLog::record([
            'action' => 'update',
            'module' => 'stations',
            'record_id' => $station->id,
            'description' => "Station updated: {$station->name}",
            'new_values' => $station->only(['code', 'name', 'status']),
        ]);

        return redirect()->route('stations.show', $station)->with('success', 'Station updated.');
    }

    protected function rules(?int $ignore = null): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('stations', 'code')->ignore($ignore)],
            'name' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'opening_date' => ['nullable', 'date'],
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(Station::STATUSES)],
            'notes' => ['nullable', 'string'],
        ];
    }
}