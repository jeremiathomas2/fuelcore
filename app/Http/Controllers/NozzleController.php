<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FuelProduct;
use App\Models\Nozzle;
use App\Models\Pump;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NozzleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $nozzles = Nozzle::query()
            ->with(['pump.station', 'fuelProduct'])
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('pump_id'), fn ($q, $v) => $q->where('pump_id', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('nozzles.index', [
            'nozzles' => $nozzles,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('nozzles.create', [
            'stations' => $this->visibleStations($request->user())->with('pumps')->orderBy('code')->get(),
            'pumps' => Pump::query()->whereIn('station_id', $this->visibleStations($request->user())->pluck('id'))->get(),
            'products' => FuelProduct::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $pump = Pump::findOrFail($data['pump_id']);
        $this->authorize('access-station', $pump->station);

        $nozzle = Nozzle::create([
            ...$data,
            'station_id' => $pump->station_id,
        ]);

        AuditLog::record([
            'action' => 'create',
            'module' => 'nozzles',
            'record_id' => $nozzle->id,
            'description' => "Nozzle {$nozzle->nozzle_number} created on pump {$pump->pump_number}",
            'new_values' => $data,
        ]);

        return redirect()->route('nozzles.index')->with('success', 'Nozzle created.');
    }

    public function edit(Nozzle $nozzle): View
    {
        $this->authorize('access-station', $nozzle->station);

        return view('nozzles.edit', [
            'nozzle' => $nozzle,
            'pumps' => Pump::query()->whereIn('station_id', [request()->user()->station_id, $nozzle->station_id])->get(),
            'products' => FuelProduct::where('active', true)->orderBy('name')->get(),
            'stations' => \App\Models\Station::query()->visibleTo(request()->user())->with('pumps')->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Nozzle $nozzle): RedirectResponse
    {
        $this->authorize('access-station', $nozzle->station);

        $data = $request->validate($this->rules($nozzle->id, $nozzle->pump_id));
        $pump = Pump::findOrFail($data['pump_id']);

        $nozzle->update([...$data, 'station_id' => $pump->station_id]);

        AuditLog::record([
            'action' => 'update',
            'module' => 'nozzles',
            'record_id' => $nozzle->id,
            'description' => "Nozzle {$nozzle->nozzle_number} updated",
            'new_values' => $nozzle->only(['status', 'fuel_product_id']),
        ]);

        return redirect()->route('nozzles.index')->with('success', 'Nozzle updated.');
    }

    protected function rules(?int $ignore = null, ?int $pumpId = null): array
    {
        return [
            'pump_id' => ['required', 'integer', 'exists:pumps,id'],
            'fuel_product_id' => ['required', 'integer', 'exists:fuel_products,id'],
            'nozzle_number' => ['required', 'string', 'max:10', Rule::unique('nozzles')->where('pump_id', $pumpId ?? request('pump_id'))->ignore($ignore)],
            'meter_start' => ['nullable', 'numeric', 'min:0'],
            'meter_current' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(Nozzle::STATUSES)],
        ];
    }
}