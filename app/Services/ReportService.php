<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\FuelDelivery;
use App\Models\FuelProduct;
use App\Models\FuelTransaction;
use App\Models\InventoryMovement;
use App\Models\Payment;
use App\Models\Reconciliation;
use App\Models\Shift;
use App\Models\Station;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Apply standard filters to a query.
     */
    private function scope(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['from'] ?? null, fn ($s, $v) => $s->where('transacted_at', '>=', $v instanceof Carbon ? $v : Carbon::parse($v)->startOfDay()))
            ->when($f['to'] ?? null, fn ($s, $v) => $s->where('transacted_at', '<=', $v instanceof Carbon ? $v : Carbon::parse($v)->endOfDay()))
            ->when($f['station_id'] ?? null, fn ($s, $v) => $s->where('station_id', $v))
            ->when($f['fuel_product_id'] ?? null, fn ($s, $v) => $s->where('fuel_product_id', $v))
            ->when($f['attendant_id'] ?? null, fn ($s, $v) => $s->where('attendant_id', $v));
    }

    private function dateExpr(): string
    {
        return DB::getDriverName() === 'sqlite' ? "DATE(transacted_at)" : "DATE(transacted_at)";
    }

    // ----------------------------------------------------------------
    // SALES REPORTS
    // ----------------------------------------------------------------

    public function salesByDate(array $f = []): array
    {
        $rows = $this->scope(FuelTransaction::query(), $f)
            ->where('fuel_transactions.status', 'completed')
            ->selectRaw("{$this->dateExpr()} as label, SUM(litres) as litres, SUM(net_amount) as revenue, COUNT(*) as count")
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return ['rows' => $rows, 'totals' => $this->totals($rows)];
    }

    public function salesByStation(array $f = []): array
    {
        $rows = $this->scope(FuelTransaction::query(), $f)
            ->where('fuel_transactions.status', 'completed')
            ->join('stations', 'stations.id', '=', 'fuel_transactions.station_id')
            ->selectRaw('stations.name as label, stations.code, SUM(litres) as litres, SUM(net_amount) as revenue, COUNT(*) as count')
            ->groupBy('stations.id', 'stations.name', 'stations.code')
            ->orderByDesc('revenue')
            ->get();

        return ['rows' => $rows, 'totals' => $this->totals($rows)];
    }

    public function salesByFuel(array $f = []): array
    {
        $rows = $this->scope(FuelTransaction::query(), $f)
            ->where('fuel_transactions.status', 'completed')
            ->join('fuel_products', 'fuel_products.id', '=', 'fuel_transactions.fuel_product_id')
            ->selectRaw('fuel_products.name as label, fuel_products.code as code, SUM(litres) as litres, SUM(net_amount) as revenue, COUNT(*) as count')
            ->groupBy('fuel_products.id', 'fuel_products.name', 'fuel_products.code')
            ->orderByDesc('revenue')
            ->get();

        return ['rows' => $rows, 'totals' => $this->totals($rows)];
    }

    public function salesByPump(array $f = []): array
    {
        $rows = $this->scope(FuelTransaction::query(), $f)
            ->where('fuel_transactions.status', 'completed')
            ->whereNotNull('pump_id')
            ->join('pumps', 'pumps.id', '=', 'fuel_transactions.pump_id')
            ->join('stations', 'stations.id', '=', 'pumps.station_id')
            ->selectRaw("CONCAT(stations.code, ' ', pumps.pump_number) as label, SUM(litres) as litres, SUM(net_amount) as revenue, COUNT(*) as count")
            ->groupBy('pumps.id', 'stations.code', 'pumps.pump_number')
            ->orderByDesc('revenue')
            ->get();

        return ['rows' => $rows, 'totals' => $this->totals($rows)];
    }

    public function salesByNozzle(array $f = []): array
    {
        $rows = $this->scope(FuelTransaction::query(), $f)
            ->where('fuel_transactions.status', 'completed')
            ->whereNotNull('nozzle_id')
            ->join('nozzles', 'nozzles.id', '=', 'fuel_transactions.nozzle_id')
            ->join('pumps', 'pumps.id', '=', 'nozzles.pump_id')
            ->join('fuel_products', 'fuel_products.id', '=', 'nozzles.fuel_product_id')
            ->selectRaw("CONCAT(pumps.pump_number, ' ', nozzles.nozzle_number, ' ', fuel_products.name) as label, SUM(litres) as litres, SUM(net_amount) as revenue, COUNT(*) as count")
            ->groupBy('nozzles.id')
            ->orderByDesc('revenue')
            ->get();

        return ['rows' => $rows, 'totals' => $this->totals($rows)];
    }

    public function salesByAttendant(array $f = []): array
    {
        $rows = $this->scope(FuelTransaction::query(), $f)
            ->where('fuel_transactions.status', 'completed')
            ->whereNotNull('attendant_id')
            ->join('users', 'users.id', '=', 'fuel_transactions.attendant_id')
            ->selectRaw('users.name as label, SUM(litres) as litres, SUM(net_amount) as revenue, COUNT(*) as count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->get();

        return ['rows' => $rows, 'totals' => $this->totals($rows)];
    }

    public function salesByPayment(array $f = []): array
    {
        $rows = Payment::query()
            ->where('status', 'paid')
            ->whereHas('transaction', fn ($q) => $this->scope($q, $f)->where('fuel_transactions.status', 'completed'))
            ->selectRaw('payments.method as label, SUM(payments.amount) as revenue, COUNT(*) as count')
            ->groupBy('payments.method')
            ->orderByDesc('revenue')
            ->get();

        $rows->each(fn ($r) => $r->label = ucfirst(str_replace('_', ' ', $r->label)));

        return ['rows' => $rows, 'totals' => ['revenue' => $rows->sum('revenue'), 'count' => $rows->sum('count'), 'litres' => null]];
    }

    // ----------------------------------------------------------------
    // INVENTORY REPORT
    // ----------------------------------------------------------------

    public function inventoryLevels(array $f = []): array
    {
        $query = InventoryMovement::query()
            ->selectRaw('station_id, fuel_product_id, SUM(quantity) as net_qty, MAX(balance_after) as last_balance')
            ->groupBy('station_id', 'fuel_product_id');

        if (! empty($f['from'])) {
            $query->where('created_at', '>=', Carbon::parse($f['from'])->startOfDay());
        }
        if (! empty($f['to'])) {
            $query->where('created_at', '<=', Carbon::parse($f['to'])->endOfDay());
        }
        if (! empty($f['station_id'])) {
            $query->where('station_id', $f['station_id']);
        }

        $rows = $query->with(['station', 'product'])->get()
            ->map(fn ($m) => [
                'label' => "{$m->station?->code} — {$m->product?->name}",
                'last_balance' => $m->last_balance,
                'net_movement' => $m->net_qty,
            ]);

        return ['rows' => $rows, 'totals' => []];
    }

    // ----------------------------------------------------------------
    // SHIFTS / RECONCILIATION / EXPENSES / DELIVERIES / CUSTOMERS
    // ----------------------------------------------------------------

    public function shifts(array $f = []): array
    {
        $rows = Shift::query()
            ->when($f['station_id'] ?? null, fn ($q, $v) => $q->where('station_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('opened_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('opened_at', '<=', Carbon::parse($v)->endOfDay()))
            ->with('station', 'employee')
            ->latest('opened_at')
            ->get();

        return ['rows' => $rows, 'totals' => ['opening' => $rows->sum('opening_cash'), 'actual' => $rows->sum('actual_cash'), 'variance' => $rows->sum('cash_variance')]];
    }

    public function reconciliations(array $f = []): array
    {
        $rows = Reconciliation::query()
            ->when($f['station_id'] ?? null, fn ($q, $v) => $q->where('station_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->with('station')
            ->latest('created_at')
            ->get();

        return ['rows' => $rows, 'totals' => ['variance_litres' => $rows->sum('variance_litres'), 'variance_value' => $rows->sum('variance_value')]];
    }

    public function expenses(array $f = []): array
    {
        $rows = Expense::query()
            ->when($f['station_id'] ?? null, fn ($q, $v) => $q->where('station_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('expense_date', '>=', Carbon::parse($v)->toDateString()))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('expense_date', '<=', Carbon::parse($v)->toDateString()))
            ->with('station', 'category', 'createdBy')
            ->latest('expense_date')
            ->get();

        return ['rows' => $rows, 'totals' => ['total' => $rows->sum('amount')]];
    }

    public function deliveries(array $f = []): array
    {
        $rows = FuelDelivery::query()
            ->when($f['station_id'] ?? null, fn ($q, $v) => $q->where('station_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('delivery_date', '>=', Carbon::parse($v)->toDateString()))
            ->with('station', 'supplier')
            ->latest('delivery_date')
            ->get();

        return ['rows' => $rows, 'totals' => ['ordered' => $rows->sum('ordered_qty'), 'delivered' => $rows->sum('delivered_qty'), 'variance' => $rows->sum('variance')]];
    }

    public function customers(array $f = []): array
    {
        $rows = Customer::query()
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->withCount('transactions')
            ->withSum('transactions as total_spent', 'net_amount')
            ->get()
            ->map(fn ($c) => ['label' => $c->name, 'transactions' => $c->transactions_count, 'total_spent' => $c->transactions_sum_net_amount ?? 0]);

        return ['rows' => $rows, 'totals' => ['total_spent' => $rows->sum('total_spent')]];
    }

    public function stationPerformance(array $f = []): array
    {
        return $this->salesByStation($f);
    }

    private function totals($rows): array
    {
        return [
            'litres' => $rows->sum('litres'),
            'revenue' => $rows->sum('revenue'),
            'count' => $rows->sum('count'),
        ];
    }
}