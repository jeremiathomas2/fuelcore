<?php

namespace App\Http\Controllers;

use App\Models\FuelProduct;
use App\Models\InventoryMovement;
use App\Models\Station;
use App\Models\Tank;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stock = Station::query()
            ->visibleTo($user)
            ->with(['tanks.fuelProduct'])
            ->when($request->filled('station_id'), fn ($q, $v) => $q->whereKey($v))
            ->orderBy('code')
            ->get()
            ->map(function (Station $station) {
                $byProduct = $station->tanks->groupBy('fuel_product_id');

                return [
                    'station' => $station,
                    'products' => FuelProduct::whereIn('id', $byProduct->keys())
                        ->get()
                        ->map(fn ($p) => [
                            'product' => $p,
                            'tanks' => $byProduct->get($p->id, collect()),
                            'stock' => $byProduct->get($p->id, collect())->sum('current_volume'),
                            'capacity' => $byProduct->get($p->id, collect())->sum('capacity'),
                        ]),
                ];
            });

        $lowStock = collect();
        foreach ($stock as $row) {
            foreach ($row['products'] as $p) {
                if ((float) $p['stock'] <= (float) $p['product']->min_stock || $p['product']->min_stock > 0 && (float) $p['stock'] / max((float) $p['capacity'], 1) < 0.15) {
                    $lowStock->push(['station' => $row['station'], 'product' => $p['product'], 'stock' => $p['stock']]);
                }
            }
        }

        $summary = ['litres' => $stock->sum(fn ($r) => collect($r['products'])->sum('stock'))];

        return view('inventory.index', [
            'stock' => $stock,
            'lowStock' => $lowStock,
            'summary' => $summary,
            'products' => FuelProduct::where('active', true)->get(),
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
        ]);
    }

    public function movements(Request $request): View
    {
        $user = $request->user();

        $movements = InventoryMovement::query()
            ->with(['station', 'product', 'tank', 'createdBy'])
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->filled('from'), fn ($q, $v) => $q->whereDate('created_at', '>=', \Illuminate\Support\Carbon::parse($v)))
            ->when($request->filled('to'), fn ($q, $v) => $q->whereDate('created_at', '<=', \Illuminate\Support\Carbon::parse($v)))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('inventory.movements', [
            'movements' => $movements,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'fuel_product_id' => ['required', 'integer', 'exists:fuel_products,id'],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'tank_id' => ['nullable', 'integer', 'exists:tanks,id'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $station = Station::findOrFail($data['station_id']);
        $this->authorize('access-station', $station);

        // Guard against over-drawing from tank stock.
        if ((float) $data['quantity'] < 0) {
            $available = app(InventoryService::class)->stockOf($station, $data['fuel_product_id']);
            if (abs((float) $data['quantity']) > $available) {
                return back()->withErrors(['quantity' => "Adjustment exceeds available stock ({$available} L)."]);
            }
        }

        app(InventoryService::class)->adjust(
            $station,
            FuelProduct::findOrFail($data['fuel_product_id']),
            (float) $data['quantity'],
            $data['tank_id'] ? Tank::find($data['tank_id']) : null,
            $data['note'],
            $user,
        );

        return redirect()->route('inventory.index')->with('success', 'Inventory adjustment recorded.');
    }
}