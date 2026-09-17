<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FuelProduct;
use App\Models\InventoryMovement;
use App\Models\Station;
use App\Models\Tank;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Current total stock of a product at a station.
     */
    public function stockOf(Station $station, FuelProduct|int $product): float
    {
        $productId = $product instanceof FuelProduct ? $product->id : $product;

        return (float) $station->tanks()
            ->where('fuel_product_id', $productId)
            ->sum('current_volume');
    }

    /**
     * Record an auditable inventory movement and mutate tank stock when appropriate.
     *
     * @param  array<string, mixed>  $opts
     */
    public function record(array $opts): InventoryMovement
    {
        $station = $opts['station'] ?? Station::findOrFail($opts['station_id']);
        $product = $opts['product'] ?? FuelProduct::findOrFail($opts['fuel_product_id']);
        $qty = (float) $opts['quantity'];
        $type = $opts['type'] ?? 'adjustment';
        $tank = $opts['tank'] ?? null;
        $ref = $opts['ref'] ?? null;
        $note = $opts['note'] ?? null;
        $user = $opts['user'] ?? auth()->user();

        return DB::transaction(function () use ($station, $product, $qty, $type, $tank, $ref, $note, $user, $opts) {
            $this->applyTankStock($station, $product, $qty, $type, $tank, $ref);

            $movement = InventoryMovement::create([
                'station_id' => $station->id,
                'fuel_product_id' => $product->id,
                'tank_id' => $opts['tank_id'] ?? ($tank?->id ?? null),
                'type' => $type,
                'quantity' => $qty,
                'unit_cost' => $opts['unit_cost'] ?? ($product->cost_price ?: null),
                'total_cost' => $opts['total_cost'] ?? ($product->cost_price ? abs($qty * (float) $product->cost_price) : null),
                'ref_type' => $ref ? get_class($ref) : ($opts['ref_type'] ?? null),
                'ref_id' => $ref?->id ?? ($opts['ref_id'] ?? null),
                'balance_after' => $this->stockOf($station, $product),
                'note' => $note,
                'created_by' => $user?->id,
            ]);

            return $movement;
        });
    }

    /**
     * Allocate a stock change to underground tanks.
     */
    private function applyTankStock(Station $station, FuelProduct $product, float $qty, string $type, ?Tank $tank, mixed $ref): void
    {
        $tanks = $station->tanks()->where('fuel_product_id', $product->id)->get();

        // Reduce stock (sale / transfer_out / return-out / correction-down)
        if ($qty < 0) {
            $remaining = abs($qty);

            $candidates = $tanks->reject(fn ($t) => $t->current_volume <= 0);

            foreach ($candidates->sortByDesc('current_volume') as $t) {
                if ($remaining <= 0) {
                    break;
                }
                $take = min((float) $t->current_volume, $remaining);
                if ($take <= 0) {
                    continue;
                }
                $t->decrement('current_volume', $take);
                $t->status = $t->evaluateStatus();
                $t->last_reading_at = now();
                $t->save();
                $remaining -= $take;
            }
        }

        // Increase stock (delivery / adjustment-up / reversal of sale)
        if ($qty > 0) {
            $targetTank = $tank ?? $tanks->sortByDesc('current_volume')->first();

            if ($targetTank) {
                $capacity = (float) $targetTank->capacity;
                $targetTank->increment('current_volume', $qty);
                if ($targetTank->current_volume > $capacity) {
                    $targetTank->current_volume = $capacity;
                }
                $targetTank->status = $targetTank->evaluateStatus();
                $targetTank->last_reading_at = now();
                $targetTank->save();
            }
        }

        // Refresh statuses on all tanks of this product when types demand it.
        if (in_array($type, ['delivery', 'sale', 'correction', 'adjustment'], true)) {
            $tanks->each(function (Tank $t) {
                $t->status = $t->evaluateStatus();
                $t->save();
            });
        }
    }

    /**
     * Convenience: record a manual stock adjustment and audit it.
     */
    public function adjust(Station $station, FuelProduct $product, float $qty, ?Tank $tank, string $reason, mixed $user = null): InventoryMovement
    {
        $movement = $this->record([
            'station' => $station,
            'product' => $product,
            'quantity' => $qty,
            'type' => 'adjustment',
            'tank' => $tank,
            'note' => $reason,
            'user' => $user,
        ]);

        AlertService::raise(
            severity: abs($qty) > 500 ? 'warning' : 'info',
            title: 'Stock adjustment recorded',
            message: "{$product->name} adjusted by {$qty} L at {$station->name} ({$reason})",
            stationId: $station->id,
        );

        AuditLog::record([
            'action' => 'adjustment',
            'module' => 'inventory',
            'record_id' => $movement->id,
            'description' => "Inventory adjusted: {$product->name} {$qty} L @ {$station->code}",
            'new_values' => ['quantity' => $qty, 'note' => $reason],
        ]);

        return $movement;
    }
}