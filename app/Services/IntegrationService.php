<?php

namespace App\Services;

use App\Models\FuelTransaction;
use App\Models\IntegrationTransaction;
use App\Models\Nozzle;
use App\Models\Pump;
use App\Models\Station;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntegrationService
{
    /**
     * Idempotent ingestion of a fuel transaction from an external pump/FCC/POS.
     * Duplicate UUIDs are silently accepted and marked "duplicate" (no duplicate sale).
     *
     * @param  array<string, mixed>  $payload
     *
     * @return array{status: string, transaction_id?: int|null, error?: string|null}
     */
    public function ingestFuelTransaction(array $payload): array
    {
        $uuid = $payload['transaction_uuid'] ?? null;

        if (! $uuid) {
            return ['status' => 'failed', 'error' => 'transaction_uuid is required'];
        }

        // Idempotency gate.
        $existing = IntegrationTransaction::where('transaction_uuid', $uuid)->first();

        if ($existing) {
            if ($existing->status === 'processed') {
                return ['status' => 'duplicate', 'transaction_id' => null];
            }

            // Allow retry of failed previous attempts (max 3 retries)
            if ($existing->retry_count >= 3) {
                return ['status' => 'failed', 'error' => 'max retries exceeded'];
            }
            $existing->increment('retry_count');
            $existing->update(['status' => 'received', 'error_message' => null]);
        } else {
            $existing = IntegrationTransaction::create([
                'station_code' => $payload['station_code'] ?? 'unknown',
                'transaction_uuid' => $uuid,
                'payload' => $payload,
                'status' => 'received',
                'received_at' => $payload['timestamp'] ?? now(),
            ]);
        }

        try {
            $station = Station::where('code', $existing->station_code)->first();
            if (! $station) {
                $existing->update(['status' => 'failed', 'error_message' => 'unknown station_code: ' . $existing->station_code, 'sync_status' => 'failed']);
                return ['status' => 'failed', 'error' => 'unknown station'];
            }

            $pump = ! empty($payload['pump_id']) ? Pump::find($payload['pump_id']) : null;
            $nozzle = ! empty($payload['nozzle_id']) ? Nozzle::find($payload['nozzle_id']) : null;

            $data = [
                'station_id' => $station->id,
                'pump_id' => $pump?->id,
                'nozzle_id' => $nozzle?->id,
                'fuel_product_id' => $payload['fuel_product_id'] ?? ($nozzle?->fuel_product_id ?? 1),
                'litres' => $payload['litres'] ?? 0,
                'amount' => $payload['amount'] ?? 0,
                'price' => $payload['price'] ?? null,
                'meter_start' => $payload['meter_start'] ?? 0,
                'meter_end' => $payload['meter_end'] ?? 0,
                'payment_method' => ($payload['payment_method'] ?? 'cash') === 'fleet' ? 'fleet_account' : ($payload['payment_method'] ?? 'cash'),
                'payment_status' => $payload['payment_status'] ?? 'paid',
                'attendant_id' => $payload['attendant_id'] ?? null,
                'customer_id' => $payload['customer_id'] ?? null,
                'fleet_account_id' => $payload['fleet_account_id'] ?? null,
                'vehicle_id' => $payload['vehicle_id'] ?? null,
                'sync_status' => 'synced',
                'external_id' => $uuid,
                'received_at' => $payload['timestamp'] ?? now(),
                'uuid' => $uuid,
                'transaction_number' => $payload['transaction_number'] ?? null,
                'transacted_at' => $payload['timestamp'] ?? now(),
            ];

            $sale = app(FuelSaleService::class)->create($data);

            $existing->update(['status' => 'processed', 'processed_at' => now(), 'sync_status' => 'success']);

            return ['status' => 'processed', 'transaction_id' => $sale->id];
        } catch (\Throwable $e) {
            \Log::error('Integration ingestion failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            $existing->update(['status' => 'failed', 'error_message' => substr($e->getMessage(), 0, 255), 'sync_status' => 'failed']);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
}