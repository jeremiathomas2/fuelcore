<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FuelDelivery;
use App\Models\FuelDeliveryItem;
use App\Models\FuelProduct;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $user = null): FuelDelivery
    {
        return DB::transaction(function () use ($data, $user) {
            $station = Station::findOrFail($data['station_id']);

            $delivery = FuelDelivery::create([
                'delivery_number' => $data['delivery_number'] ?? 'DLV-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                'station_id' => $station->id,
                'supplier_id' => $data['supplier_id'],
                'driver_name' => $data['driver_name'] ?? null,
                'vehicle_number' => $data['vehicle_number'] ?? null,
                'ordered_qty' => (float) ($data['ordered_qty'] ?? 0),
                'delivery_date' => $data['delivery_date'] ?? now(),
                'receiving_employee' => $data['receiving_employee'] ?? $user?->name,
                'status' => $data['status'] ?? 'scheduled',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach (($data['items'] ?? []) as $item) {
                if (empty($item['fuel_product_id'])) {
                    continue;
                }

                FuelDeliveryItem::create([
                    'fuel_delivery_id' => $delivery->id,
                    'fuel_product_id' => $item['fuel_product_id'],
                    'tank_id' => $item['tank_id'] ?? null,
                    'ordered_qty' => (float) ($item['ordered_qty'] ?? 0),
                    'delivered_qty' => (float) ($item['delivered_qty'] ?? 0),
                    'expected_qty' => (float) ($item['expected_qty'] ?? 0),
                    'unit_price' => $item['unit_price'] ?? FuelProduct::find($item['fuel_product_id'])?->cost_price,
                ]);
            }

            AuditLog::record([
                'action' => 'create',
                'module' => 'fuel_deliveries',
                'record_id' => $delivery->id,
                'description' => "Delivery {$delivery->delivery_number} scheduled for {$station->name}",
                'new_values' => ['status' => $delivery->status],
            ]);

            return $delivery;
        });
    }

    /**
     * Complete a delivery: mark delivered quantities, restock tanks, create inventory
     * movements and raise variance alerts. Status transitions to reconciled.
     */
    public function complete(FuelDelivery $delivery, array $delivered, ?User $user = null): FuelDelivery
    {
        return DB::transaction(function () use ($delivery, $delivered, $user) {
            $totalOrdered = 0.0;
            $totalDelivered = 0.0;

            foreach ($delivered as $itemId => $row) {
                $item = FuelDeliveryItem::findOrFail($itemId);
                $qty = (float) ($row['delivered_qty'] ?? 0);
                $tankId = $row['tank_id'] ?? $item->tank_id;
                $tank = $tankId ? Tank::find($tankId) : null;

                if ($qty < 0) {
                    throw new \InvalidArgumentException('Delivered quantity cannot be negative.');
                }

                if ($tank && $tank->station_id !== $delivery->station_id) {
                    throw new \InvalidArgumentException('Target tank does not belong to the delivery station.');
                }

                $item->update([
                    'delivered_qty' => $qty,
                    'tank_id' => $tank?->id,
                    'total_cost' => $qty * (float) ($item->unit_price ?? 0),
                ]);

                if ($qty > 0) {
                    app(InventoryService::class)->record([
                        'station' => $delivery->station,
                        'product' => FuelProduct::find($item->fuel_product_id),
                        'quantity' => $qty,
                        'type' => 'delivery',
                        'tank' => $tank,
                        'ref' => $delivery,
                        'note' => "Delivery {$delivery->delivery_number} received",
                        'user' => $user,
                    ]);
                }

                $totalOrdered += (float) $item->ordered_qty;
                $totalDelivered += $qty;
            }

            $variance = round($totalDelivered - $totalOrdered, 3);
            $delivery->update([
                'delivered_qty' => $totalDelivered,
                'variance' => $variance,
                'status' => 'reconciled',
                'completed_by' => $user?->id,
                'completed_at' => now(),
            ]);

            if (abs($variance) > 10) {
                AlertService::raise(
                    severity: 'warning',
                    title: 'Delivery variance detected',
                    message: "Delivery {$delivery->delivery_number} variance of {$variance} L for {$delivery->station->name}",
                    stationId: $delivery->station_id,
                );
            }

            AuditLog::record([
                'action' => 'receive',
                'module' => 'fuel_deliveries',
                'record_id' => $delivery->id,
                'description' => "Delivery {$delivery->delivery_number} received: {$totalDelivered} L (variance {$variance} L)",
                'old_values' => [],
                'new_values' => ['delivered_qty' => $totalDelivered, 'variance' => $variance, 'status' => 'reconciled'],
            ]);

            return $delivery->fresh();
        });
    }
}