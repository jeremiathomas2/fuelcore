<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\FuelDelivery;
use App\Models\FuelProduct;
use App\Models\InventoryMovement;
use App\Models\Reconciliation;
use App\Models\Shift;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    /**
     * Compute and persist the fuel reconciliation for a station.
     *
     *  Opening + Deliveries + Adjustments - Sales = Expected Closing
     *  Variance = Actual tank reading - Expected Closing
     *
     * @param  array<string, mixed>  $options
     */
    public function run(Station $station, array $options = [], ?User $user = null): Reconciliation
    {
        $shift = null;
        if (! empty($options['shift_id'])) {
            $shift = Shift::find($options['shift_id']);
        }

        $from = $options['from'] ?? ($shift?->opened_at ?? now()->startOfDay());
        $from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $to = $options['to'] ?? ($shift?->closed_at ?? now()->endOfDay());
        $to = $to instanceof Carbon ? $to : Carbon::parse($to);

        $products = FuelProduct::where('active', true)->get();

        $totals = [
            'opening' => 0.0,
            'deliveries' => 0.0,
            'adjustments' => 0.0,
            'sales' => 0.0,
            'expected' => 0.0,
            'actual' => (float) $station->tanks()->sum('current_volume'),
        ];

        foreach ($products as $product) {
            // Opening = stock carried into the period from the balance of the last
            // movement before $from for this product at this station, else current tanks.
            $priorMovement = InventoryMovement::where('station_id', $station->id)
                ->where('fuel_product_id', $product->id)
                ->where('created_at', '<', $from)
                ->latest('created_at')
                ->first();

            $opening = $priorMovement ? (float) $priorMovement->balance_after
                : (float) $station->tanks()->where('fuel_product_id', $product->id)->sum('current_volume');

            $deliveries = (float) InventoryMovement::where('station_id', $station->id)
                ->where('fuel_product_id', $product->id)
                ->where('type', 'delivery')
                ->whereBetween('created_at', [$from, $to])
                ->sum('quantity');

            $adjustments = (float) InventoryMovement::where('station_id', $station->id)
                ->where('fuel_product_id', $product->id)
                ->whereIn('type', ['adjustment', 'purchase', 'transfer_in', 'correction'])
                ->whereBetween('created_at', [$from, $to])
                ->sum('quantity');

            $sales = (float) abs(InventoryMovement::where('station_id', $station->id)
                ->where('fuel_product_id', $product->id)
                ->where('type', 'sale')
                ->whereBetween('created_at', [$from, $to])
                ->sum('quantity'));

            $expected = $opening + $deliveries + $adjustments - $sales;

            $totals['opening'] += $opening;
            $totals['deliveries'] += $deliveries;
            $totals['adjustments'] += $adjustments;
            $totals['sales'] += $sales;
            $totals['expected'] += $expected;
        }

        $varianceLitres = round($totals['actual'] - $totals['expected'], 3);
        $variancePct = $totals['expected'] != 0 ? round($varianceLitres / $totals['expected'] * 100, 3) : 0;
        $avgPrice = (float) (FuelProduct::where('active', true)->avg('price') ?? 0);
        $varianceValue = round($varianceLitres * $avgPrice, 2);

        $threshold = (float) options('variance_warning_threshold', 1.0);

        $reconciliation = Reconciliation::updateOrCreate(
            [
                'station_id' => $station->id,
                'shift_id' => $shift?->id,
                'period_start' => $from->toDateString(),
            ],
            [
                'period_end' => $to->toDateString(),
                'opening_stock' => $totals['opening'],
                'deliveries_qty' => $totals['deliveries'],
                'adjustments_qty' => $totals['adjustments'],
                'sales_qty' => $totals['sales'],
                'expected_closing' => $totals['expected'],
                'actual_reading' => $totals['actual'],
                'variance_litres' => $varianceLitres,
                'variance_pct' => $variancePct,
                'variance_value' => $varianceValue,
                'reconciled_at' => now(),
                'reconciled_by' => $user?->id ?? auth()->id(),
                'status' => 'reconciled',
            ],
        );

        if (abs($variancePct) > $threshold) {
            AlertService::raise(
                severity: abs($variancePct) > $threshold * 2 ? 'critical' : 'warning',
                title: 'Fuel variance out of range',
                message: "Station {$station->name} variance {$varianceLitres} L ({$variancePct}%) exceeds configured threshold of {$threshold}%.",
                stationId: $station->id,
                entityType: Reconciliation::class,
                entityId: $reconciliation->id,
            );
        }

        AuditLog::record([
            'action' => 'reconcile',
            'module' => 'reconciliations',
            'record_id' => $reconciliation->id,
            'description' => "Reconciliation run for {$station->name}: {$varianceLitres} L variance",
            'new_values' => $reconciliation->only(['expected_closing', 'actual_reading', 'variance_litres', 'variance_pct']),
        ]);

        return $reconciliation;
    }
}