<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FuelProduct;
use App\Models\Tank;
use App\Models\TankReading;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TankController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tanks = Tank::query()
            ->with(['station', 'fuelProduct'])
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        $summary = Tank::query()
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->selectRaw('status, COUNT(*) as count, SUM(capacity) as capacity, SUM(current_volume) as volume')
            ->groupBy('status')
            ->get();

        return view('tanks.index', [
            'tanks' => $tanks,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
            'summary' => $summary,
        ]);
    }

    public function create(Request $request): View
    {
        return view('tanks.create', [
            'stations' => $this->visibleStations($request->user())->orderBy('code')->get(),
            'products' => FuelProduct::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $tank = Tank::create([...$data]);
        $tank->status = $tank->evaluateStatus();
        $tank->save();

        AuditLog::record([
            'action' => 'create',
            'module' => 'tanks',
            'record_id' => $tank->id,
            'description' => "Tank {$tank->tank_number} created ({$tank->capacity} L)",
            'new_values' => $tank->only(['tank_number', 'capacity', 'fuel_product_id']),
        ]);

        return redirect()->route('tanks.index')->with('success', 'Tank created.');
    }

    public function edit(Tank $tank): View
    {
        $this->authorize('access-station', $tank->station);

        return view('tanks.edit', [
            'tank' => $tank,
            'stations' => \App\Models\Station::query()->visibleTo(request()->user())->orderBy('code')->get(),
            'products' => FuelProduct::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Tank $tank): RedirectResponse
    {
        $this->authorize('access-station', $tank->station);

        $data = $request->validate($this->rules($tank->id, $tank->station_id));
        $data['status'] = (new Tank(['capacity' => $data['capacity'], 'current_volume' => $data['current_volume'] ?? 0]))->evaluateStatus();
        $tank->update($data);

        AuditLog::record([
            'action' => 'update',
            'module' => 'tanks',
            'record_id' => $tank->id,
            'description' => "Tank {$tank->tank_number} updated",
            'new_values' => $tank->only(['capacity', 'current_volume', 'status']),
        ]);

        return redirect()->route('tanks.index')->with('success', 'Tank updated.');
    }

    public function storeReading(Request $request, Tank $tank): RedirectResponse
    {
        $this->authorize('access-station', $tank->station);

        $data = $request->validate([
            'reading_litres' => ['required', 'numeric', 'min:0'],
            'temperature' => ['nullable', 'numeric'],
            'water_level' => ['nullable', 'numeric'],
            'leak_detected' => ['sometimes', 'boolean'],
            'reading_at' => ['sometimes', 'date'],
        ]);

        $tank->update([
            'current_volume' => $data['reading_litres'],
            'temperature' => $data['temperature'] ?? $tank->temperature,
            'water_level' => $data['water_level'] ?? $tank->water_level,
            'leak_status' => ! empty($data['leak_detected']) ? 'yes' : 'no',
            'status' => $tank->evaluateStatus(),
            'last_reading_at' => now(),
        ]);

        TankReading::create([
            'tank_id' => $tank->id,
            'station_id' => $tank->station_id,
            'reading_litres' => $data['reading_litres'],
            'temperature' => $data['temperature'] ?? null,
            'water_level' => $data['water_level'] ?? 0,
            'leak_detected' => $data['leak_detected'] ?? false,
            'source' => 'manual',
            'reading_at' => $data['reading_at'] ?? now(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('tanks.index')->with('success', 'Tank reading recorded.');
    }

    protected function rules(?int $ignore = null, ?int $stationId = null): array
    {
        return [
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'fuel_product_id' => ['required', 'integer', 'exists:fuel_products,id'],
            'tank_number' => ['required', 'string', 'max:10', Rule::unique('tanks')->where('station_id', $stationId ?? request('station_id'))->ignore($ignore)],
            'capacity' => ['required', 'numeric', 'min:1'],
            'current_volume' => ['nullable', 'numeric', 'min:0'],
            'min_level' => ['nullable', 'numeric', 'min:0'],
            'max_level' => ['nullable', 'numeric', 'gt:min_level'],
            'temperature' => ['nullable', 'numeric'],
            'water_level' => ['nullable', 'numeric'],
            'leak_status' => ['sometimes', Rule::in(['no', 'yes', 'unknown'])],
            'status' => ['sometimes', Rule::in(['normal', 'low', 'warning', 'critical'])],
        ];
    }
}