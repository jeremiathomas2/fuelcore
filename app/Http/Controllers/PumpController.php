<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Pump;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PumpController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $pumps = Pump::query()
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->with(['station', 'nozzles.fuelProduct'])
            ->withCount('nozzles')
            ->when($request->filled('search'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('pump_number', 'like', "%{$s}%")
                ->orWhere('manufacturer', 'like', "%{$s}%")
                ->orWhere('serial_number', 'like', "%{$s}%")))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('pumps.index', [
            'pumps' => $pumps,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('pumps.create', [
            'stations' => $this->visibleStations($request->user())->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $station = Station::findOrFail($data['station_id']);
        $this->authorize('access-station', $station);

        $pump = Pump::create($data);

        AuditLog::record([
            'action' => 'create',
            'module' => 'pumps',
            'record_id' => $pump->id,
            'description' => "Pump {$pump->pump_number} created at {$station->name}",
            'new_values' => $data,
        ]);

        return redirect()->route('pumps.index')->with('success', 'Pump created.');
    }

    public function edit(Pump $pump): View
    {
        $this->authorize('access-station', $pump->station);

        return view('pumps.edit', [
            'pump' => $pump,
            'stations' => \App\Models\Station::query()->visibleTo(request()->user())->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Pump $pump): RedirectResponse
    {
        $this->authorize('access-station', $pump->station);

        $data = $request->validate($this->rules($pump->id, $pump->station_id));
        $pump->update($data);

        AuditLog::record([
            'action' => 'update',
            'module' => 'pumps',
            'record_id' => $pump->id,
            'description' => "Pump {$pump->pump_number} updated",
            'new_values' => $pump->only(['status']),
        ]);

        return redirect()->route('pumps.index')->with('success', 'Pump updated.');
    }

    protected function rules(?int $ignore = null, ?int $stationId = null): array
    {
        return [
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'pump_number' => ['required', 'string', 'max:10', Rule::unique('pumps')->where('station_id', $stationId ?? request('station_id'))->ignore($ignore)],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'controller_address' => ['nullable', 'string', 'max:255'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(Pump::STATUSES)],
            'installation_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}