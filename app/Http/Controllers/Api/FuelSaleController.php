<?php

namespace App\Http\Controllers\Api;

use App\Models\FuelTransaction;
use App\Models\Station;
use App\Services\IntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FuelSaleController extends Controller
{
    public function __construct(private IntegrationService $integrationService)
    {
    }

    public function ingest(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'transaction_uuid' => 'required|string',
            'station_code' => 'required|string',
            'fuel_product_id' => 'required|integer',
            'litres' => 'required|numeric|min:0.001',
            'amount' => 'required|numeric|min:0',
            'price' => 'nullable|numeric',
            'meter_start' => 'nullable|numeric',
            'meter_end' => 'nullable|numeric',
            'payment_method' => 'sometimes|string|in:cash,mobile_money,card,credit,fleet,fleet_account',
            'payment_status' => 'sometimes|string',
            'attendant_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'fleet_account_id' => 'nullable|integer',
            'vehicle_id' => 'nullable|integer',
            'pump_id' => 'nullable|integer|exists:pumps,id',
            'nozzle_id' => 'nullable|integer|exists:nozzles,id',
            'timestamp' => 'sometimes|date',
            'transaction_number' => 'nullable|string',
        ]);

        $result = $this->integrationService->ingestFuelTransaction($payload);

        return response()->json($result, $result['status'] === 'failed' ? 422 : 200);
    }

    public function stationTransactions(Request $request, Station $station): JsonResponse
    {
        $transactions = FuelTransaction::query()
            ->where('station_id', $station->id)
            ->when($request->filled('from'), fn ($q) => $q->where('transacted_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('transacted_at', '<=', $request->date('to')))
            ->latest('transacted_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json($transactions);
    }
}