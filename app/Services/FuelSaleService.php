<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FuelProduct;
use App\Models\FuelTransaction;
use App\Models\FuelTransactionItem;
use App\Models\Nozzle;
use App\Models\Payment;
use App\Models\Pump;
use App\Models\Shift;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FuelSaleService
{
    /**
     * Atomically persist a fuel sale:
     * transaction + item + payment + inventory movement + meter/tank updates + audit.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \InvalidArgumentException
     */
    public function create(array $data, ?User $user = null): FuelTransaction
    {
        $station = Station::findOrFail($data['station_id']);
        $product = FuelProduct::findOrFail($data['fuel_product_id']);

        $fuelType = $data['fuel_type'] ?? 'volume'; // volume | amount
        $litres = (float) ($data['litres'] ?? 0);
        $amountGiven = (float) ($data['amount'] ?? 0);
        $price = max(0.01, (float) ($data['price'] ?? $product->price));

        if ($fuelType === 'amount') {
            $litres = round($amountGiven / $price, 3);
        }

        if ($litres <= 0) {
            throw new \InvalidArgumentException('A positive fuel quantity is required.');
        }

        $pump = $data['pump_id'] ?? null ? Pump::find($data['pump_id']) : null;
        $nozzle = $data['nozzle_id'] ?? null ? Nozzle::find($data['nozzle_id']) : null;

        if ($pump && $pump->station_id !== $station->id) {
            throw new \InvalidArgumentException('The selected pump does not belong to this station.');
        }
        if ($nozzle && $nozzle->station_id !== $station->id) {
            throw new \InvalidArgumentException('The selected nozzle does not belong to this station.');
        }
        if ($nozzle && $nozzle->fuel_product_id !== $product->id) {
            throw new \InvalidArgumentException('The selected nozzle dispenses a different fuel product.');
        }

        $amount = round($litres * $price, 2);
        $discount = (float) ($data['discount'] ?? 0);
        $tax = round((float) ($data['tax'] ?? 0), 2);
        $netAmount = max(0, round($amount - $discount + $tax, 2));

        $transactedAt = isset($data['transacted_at'])
            ? Carbon::parse($data['transacted_at'])
            : now();

        $shift = $data['shift_id'] ?? null ? Shift::find($data['shift_id']) : null;

        return DB::transaction(function () use (
            $station, $product, $pump, $nozzle, $litres, $price, $amount,
            $discount, $tax, $netAmount, $data, $user, $transactedAt, $shift,
        ) {
            $party = $this->resolveParty($data);

            $seq = FuelTransaction::query()
                ->whereDate('transacted_at', $transactedAt->toDateString())
                ->count() + 1;

            $transaction = FuelTransaction::create([
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'transaction_number' => $data['transaction_number']
                    ?? 'FS-' . $transactedAt->format('Ymd') . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                'station_id' => $station->id,
                'pump_id' => $pump?->id,
                'nozzle_id' => $nozzle?->id,
                'fuel_product_id' => $product->id,
                'attendant_id' => $user?->id ?? ($data['attendant_id'] ?? null),
                'customer_id' => $party['customer_id'],
                'fleet_account_id' => $party['fleet_account_id'],
                'vehicle_id' => $party['vehicle_id'],
                'shift_id' => $shift?->id,
                'meter_start' => (float) ($data['meter_start'] ?? $nozzle?->meter_current ?? 0),
                'meter_end' => (float) ($data['meter_end'] ?? ($nozzle?->meter_current ? $nozzle->meter_current + $litres : $litres)),
                'litres' => $litres,
                'price' => $price,
                'unit_cost' => $product->cost_price ?: null,
                'amount' => $amount,
                'discount' => $discount,
                'tax' => $tax,
                'net_amount' => $netAmount,
                'payment_status' => $data['payment_status'] ?? 'paid',
                'status' => $data['status'] ?? 'completed',
                'sync_status' => $data['sync_status'] ?? 'synced',
                'external_id' => $data['external_id'] ?? null,
                'received_at' => $data['received_at'] ?? null,
                'synced_at' => ($data['sync_status'] ?? 'synced') === 'synced' ? now() : null,
                'transacted_at' => $transactedAt,
                'created_by' => $user?->id ?? ($data['created_by'] ?? null),
            ]);

            FuelTransactionItem::create([
                'fuel_transaction_id' => $transaction->id,
                'fuel_product_id' => $product->id,
                'quantity' => $litres,
                'price' => $price,
                'amount' => $amount,
            ]);

            $this->createPayment($transaction, $data, $user);

            app(InventoryService::class)->record([
                'station' => $station,
                'product' => $product,
                'quantity' => -$litres,
                'type' => 'sale',
                'ref' => $transaction,
                'note' => "Fuel sale {$transaction->transaction_number}",
                'user' => $user,
            ]);

            // Nozzle meter bookkeeping
            if ($nozzle) {
                $nozzle->increment('meter_current', $litres);
                $nozzle->increment('total_litres', $litres);
                $nozzle->last_transaction_at = $transactedAt;
                $nozzle->status = 'idle';
                $nozzle->save();
            }
            if ($pump) {
                $pump->last_communication_at = now();
                $pump->save();
            }

            AuditLog::record([
                'action' => 'create',
                'module' => 'fuel_transactions',
                'record_id' => $transaction->id,
                'description' => "Sale recorded: {$transaction->transaction_number} {$product->name} {$litres}L @ {$station->code}",
                'new_values' => ['amount' => $transaction->net_amount, 'payment' => $data['payment_method'] ?? 'cash'],
            ]);

            return $transaction;
        });
    }

    /**
     * @return array{customer_id: int|null, fleet_account_id: int|null, vehicle_id: int|null}
     */
    protected function resolveParty(array $data): array
    {
        $fleet = $data['fleet_account_id'] ?? null;
        $customer = $data['customer_id'] ?? null;
        $vehicle = $data['vehicle_id'] ?? null;

        if (empty($customer) && $fleet) {
            $customer = \App\Models\FleetAccount::find($fleet)?->customer_id;
        }

        return [
            'customer_id' => $customer,
            'fleet_account_id' => $fleet,
            'vehicle_id' => $vehicle,
        ];
    }

    public function createPayment(FuelTransaction $transaction, array $data, ?User $user = null): Payment
    {
        $allowed = ['cash', 'mobile_money', 'card', 'fleet_account', 'credit'];
        $method = in_array($data['payment_method'] ?? 'cash', $allowed, true)
            ? $data['payment_method']
            : 'cash';

        $payment = Payment::create([
            'payment_number' => 'PMT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'transaction_id' => $transaction->id,
            'amount' => $transaction->net_amount,
            'method' => $method,
            'reference' => $data['payment_reference'] ?? null,
            'provider' => $data['provider'] ?? null,
            'status' => $data['payment_status'] ?? 'paid',
            'paid_at' => $data['paid_at'] ?? ($transaction->transacted_at ?? now()),
            'created_by' => $user?->id,
        ]);

        if ($method === 'credit' && $transaction->customer_id) {
            $transaction->customer?->increment('outstanding_balance', $transaction->net_amount);
        }

        return $payment;
    }

    /**
     * Void a completed financial transaction through reversal — never silent edits.
     */
    public function void(FuelTransaction $transaction, string $reason, ?User $user = null): FuelTransaction
    {
        if (in_array($transaction->status, ['voided', 'cancelled', 'refunded'], true)) {
            throw new \InvalidArgumentException('This transaction is already reversed.');
        }

        return DB::transaction(function () use ($transaction, $reason, $user) {
            $old = $transaction->toArray();

            $transaction->update(['status' => 'voided', 'void_reason' => $reason]);

            app(InventoryService::class)->record([
                'station' => $transaction->station,
                'product' => $transaction->fuelProduct,
                'quantity' => $transaction->litres,
                'type' => 'correction',
                'ref' => $transaction,
                'note' => "Reversal of {$transaction->transaction_number}",
                'user' => $user,
            ]);

            // Reverse nozzle metering so readings/snitch metrics stay consistent.
            if ($transaction->nozzle) {
                $transaction->nozzle->decrement('meter_current', $transaction->litres);
                $transaction->nozzle->decrement('total_litres', $transaction->litres);
                $transaction->nozzle->save();
            }

            // Reverse the payment: paid -> refunded (never counted as shift cash),
            // non-paid -> cancelled to keep reconciliation accurate.
            $payment = $transaction->payment;
            if ($payment) {
                $payment->update([
                    'status' => $payment->status === 'paid' ? 'refunded' : 'cancelled',
                ]);
            }

            if ($payment && $payment->method === 'credit' && $transaction->customer_id) {
                $transaction->customer?->decrement('outstanding_balance', $transaction->net_amount);
            }

            AuditLog::record([
                'action' => 'void',
                'module' => 'fuel_transactions',
                'record_id' => $transaction->id,
                'description' => "Voided {$transaction->transaction_number} ({$transaction->litres}L, {$transaction->net_amount}) — {$reason}",
                'old_values' => $old,
                'new_values' => ['status' => 'voided', 'void_reason' => $reason],
            ]);

            return $transaction->fresh();
        });
    }
}