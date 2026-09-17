<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FuelDelivery;
use App\Models\FuelProduct;
use App\Models\Station;
use App\Models\Supplier;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $deliveries = FuelDelivery::query()
            ->with(['station', 'supplier', 'items.product'])
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('delivery_date')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('deliveries.index', [
            'deliveries' => $deliveries,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('deliveries.create', [
            'stations' => $this->visibleStations($request->user())->orderBy('code')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
            'products' => FuelProduct::where('active', true)->orderBy('name')->with('tanks')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'ordered_qty' => ['required', 'numeric', 'min:0'],
            'delivery_date' => ['required', 'date'],
            'status' => ['sometimes', Rule::in(FuelDelivery::STATUSES)],
            'notes' => ['nullable', 'string'],
            'items' => ['sometimes', 'array'],
            'items.*.fuel_product_id' => ['nullable', 'integer', 'exists:fuel_products,id'],
            'items.*.tank_id' => ['nullable', 'integer', 'exists:tanks,id'],
            'items.*.ordered_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.delivered_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $station = Station::findOrFail($data['station_id']);
        $this->authorize('access-station', $station);

        try {
            $delivery = app(DeliveryService::class)->create($data, $request->user());

            return redirect()->route('deliveries.index')
                ->with('success', "Delivery {$delivery->delivery_number} scheduled.");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(FuelDelivery $delivery): View
    {
        $this->authorize('access-station', $delivery->station);

        return view('deliveries.show', [
            'delivery' => $delivery,
            'products' => FuelProduct::where('active', true)->get(),
            'tanks' => $delivery->station->tanks()->with('fuelProduct')->get(),
        ]);
    }

    public function complete(Request $request, FuelDelivery $delivery): RedirectResponse
    {
        $this->authorize('access-station', $delivery->station);

        if ($delivery->items()->exists()) {
            $data = $request->validate([
                'delivered' => ['required', 'array'],
                'delivered.*.delivered_qty' => ['required', 'numeric', 'min:0'],
                'delivered.*.tank_id' => ['nullable', 'integer', 'exists:tanks,id'],
            ]);

            $delivered = $data['delivered'];
        } else {
            // Deliveries created without itemised rows can still be received by
            // recording the single fuel/tank quantity at completion time.
            $data = $request->validate([
                'fuel_product_id' => ['required', 'integer', 'exists:fuel_products,id'],
                'tank_id' => ['required', 'integer', 'exists:tanks,id'],
                'delivered_qty' => ['required', 'numeric', 'min:0'],
            ]);

            $item = \App\Models\FuelDeliveryItem::create([
                'fuel_delivery_id' => $delivery->id,
                'fuel_product_id' => $data['fuel_product_id'],
                'tank_id' => $data['tank_id'],
                'ordered_qty' => (float) $delivery->ordered_qty,
                'delivered_qty' => (float) $data['delivered_qty'],
                'unit_price' => FuelProduct::find($data['fuel_product_id'])?->cost_price,
            ]);

            $delivered = [$item->id => [
                'delivered_qty' => $data['delivered_qty'],
                'tank_id' => $data['tank_id'],
            ]];
        }

        try {
            app(DeliveryService::class)->complete($delivery, $delivered, $request->user());

            return redirect()->route('deliveries.show', $delivery)
                ->with('success', 'Delivery received and reconciled to tank stock.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, FuelDelivery $delivery): RedirectResponse
    {
        $this->authorize('access-station', $delivery->station);

        if (in_array($delivery->status, ['completed', 'reconciled', 'cancelled'], true)) {
            return back()->withErrors(['error' => 'Completed or already cancelled deliveries cannot be cancelled.']);
        }

        $data = $request->validate(['notes' => ['nullable', 'string', 'max:255']]);
        $oldStatus = $delivery->status;

        $delivery->update([
            'status' => 'cancelled',
            'notes' => $data['notes'] ?? $delivery->notes,
        ]);

        AuditLog::record([
            'action' => 'cancel',
            'module' => 'fuel_deliveries',
            'record_id' => $delivery->id,
            'description' => "Delivery {$delivery->delivery_number} cancelled",
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'cancelled'],
        ]);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery cancelled.');
    }
}