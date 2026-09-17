<?php

namespace App\Http\Controllers;

use App\Models\FuelTransaction;
use App\Services\FuelSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuelSalesController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $transactions = FuelTransaction::query()
            ->with(['station', 'fuelProduct', 'attendant', 'customer', 'payment'])
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->filled('payment_method'), fn ($q, $v) => $q->whereHas('payment', fn ($p) => $p->where('method', $v)))
            ->when($request->filled('product_id'), fn ($q, $v) => $q->where('fuel_product_id', $v))
            ->when($request->filled('from'), fn ($q, $v) => $q->where('transacted_at', '>=', \Illuminate\Support\Carbon::parse($v)->startOfDay()))
            ->when($request->filled('to'), fn ($q, $v) => $q->where('transacted_at', '<=', \Illuminate\Support\Carbon::parse($v)->endOfDay()))
            ->when($request->filled('search'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('transaction_number', 'like', "%{$s}%")
                ->orWhere('uuid', 'like', "%{$s}%")))
            ->latest('transacted_at')
            ->paginate($this->perPage())
            ->withQueryString();

        $totals = FuelTransaction::query()
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->where('status', 'completed')
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->selectRaw('SUM(litres) as litres, SUM(net_amount) as revenue, COUNT(*) as count')
            ->first();

        return view('sales.index', [
            'transactions' => $transactions,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
            'products' => \App\Models\FuelProduct::orderBy('name')->get(),
            'totals' => $totals,
        ]);
    }

    public function show(Request $request, FuelTransaction $transaction): JsonResponse
    {
        $this->authorize('access-station', $transaction->station);

        return response()->json($transaction->load([
            'station', 'pump', 'nozzle', 'fuelProduct', 'attendant', 'customer',
            'fleetAccount', 'shift', 'payment', 'items',
        ]));
    }

    public function void(Request $request, FuelTransaction $transaction): RedirectResponse
    {
        $this->authorize('access-station', $transaction->station);

        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            app(FuelSaleService::class)->void($transaction, $data['reason'], $request->user());

            return redirect()->route('sales.index')->with('success', "Transaction {$transaction->transaction_number} voided.");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}