<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\FuelDelivery;
use App\Models\FuelDeliveryItem;
use App\Models\FuelProduct;
use App\Models\FuelTransaction;
use App\Models\Nozzle;
use App\Models\Payment;
use App\Models\Pump;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Aggregate all dashboard statistics from the database.
     */
    public function get(array $options = []): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $sevenDaysAgo = $now->copy()->subDays(6)->startOfDay();
        $user = $options['user'] ?? null;

        $stationQuery = Station::query();
        if ($user) {
            $stationQuery->visibleTo($user);
        }
        $stationIds = $stationQuery->pluck('id');

        $stationCounts = Station::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $salesToday = FuelTransaction::query()
            ->whereIn('station_id', $stationIds)
            ->whereDate('transacted_at', $now->toDateString())
            ->where('status', 'completed')
            ->selectRaw('count(*) as tx_count, COALESCE(SUM(net_amount),0) as revenue, COALESCE(SUM(litres),0) as litres')
            ->first();

        $salesYesterday = FuelTransaction::query()
            ->whereIn('station_id', $stationIds)
            ->whereDate('transacted_at', $now->copy()->subDay()->toDateString())
            ->where('status', 'completed')
            ->sum('net_amount');

        $revenueChange = $salesYesterday > 0
            ? round((($salesToday->revenue - $salesYesterday) / $salesYesterday) * 100, 1)
            : 0;

        $fuelSoldPetrol = FuelTransaction::whereIn('station_id', $stationIds)
            ->whereDate('transacted_at', $now->toDateString())
            ->where('status', 'completed')
            ->whereHas('fuelProduct', fn ($q) => $q->where('name', 'like', '%petrol%'))
            ->sum('litres');

        $fuelSoldDiesel = FuelTransaction::whereIn('station_id', $stationIds)
            ->whereDate('transacted_at', $now->toDateString())
            ->where('status', 'completed')
            ->whereHas('fuelProduct', fn ($q) => $q->where('name', 'like', '%diesel%'))
            ->sum('litres');

        $pumpsOnline = Pump::whereIn('station_id', $stationIds)->where('status', 'online')->count();
        $pumpsTotal = Pump::whereIn('station_id', $stationIds)->count();
        $pumpsActivePct = $pumpsTotal > 0 ? round($pumpsOnline / $pumpsTotal * 100, 1) : 0;

        $nozzlesOnline = Nozzle::whereIn('station_id', $stationIds)->whereIn('status', ['idle', 'dispensing', 'completed'])->count();
        $nozzlesTotal = Nozzle::whereIn('station_id', $stationIds)->count();

        $inventoryTotal = Tank::whereIn('station_id', $stationIds)->sum('current_volume');

        // Reconstruct yesterday's opening stock: closing − received today + sold today.
        $receivedToday = FuelDeliveryItem::whereHas('delivery', fn ($q) => $q
                ->whereIn('station_id', $stationIds)
                ->whereDate('delivery_date', $now->toDateString())
                ->where('status', 'completed'))
            ->sum('delivered_qty');
        $inventoryYesterday = max(0, (float) $inventoryTotal - (float) $receivedToday + (float) $salesToday->litres);
        $inventoryChange = $inventoryYesterday > 0
            ? round((($inventoryTotal - $inventoryYesterday) / $inventoryYesterday) * 100, 1)
            : 0;

        $pendingDeliveries = FuelDelivery::whereIn('station_id', $stationIds)
            ->whereIn('status', ['scheduled', 'in_transit', 'receiving'])
            ->count();

        $activeAlerts = Alert::whereIn('station_id', $stationIds)->unresolved()->count();
        $criticalAlerts = Alert::whereIn('station_id', $stationIds)->unresolved()->where('severity', 'critical')->count();

        // Daily chart (last 7 days)
        $dailyChart = FuelTransaction::query()
            ->whereIn('station_id', $stationIds)
            ->whereBetween('transacted_at', [$sevenDaysAgo, $todayEnd])
            ->where('status', 'completed')
            ->selectRaw('DATE(transacted_at) as d, SUM(net_amount) as revenue')
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('revenue', 'd')
            ->toArray();

        // Payment breakdown today
        $paymentSummary = Payment::whereHas('transaction', fn ($q) => $q
                ->whereIn('station_id', $stationIds)
                ->whereDate('transacted_at', $now->toDateString())
                ->where('status', 'completed'))
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        // Top stations today (by revenue)
        $topStations = FuelTransaction::query()
            ->whereIn('station_id', $stationIds)
            ->whereDate('transacted_at', $now->toDateString())
            ->where('status', 'completed')
            ->selectRaw('station_id, SUM(litres) as litres, SUM(net_amount) as revenue, COUNT(*) as tx_count')
            ->groupBy('station_id')
            ->orderByDesc('revenue')
            ->with('station')
            ->get();

        // Recent transactions (dashboard table)
        $recentTransactions = FuelTransaction::query()
            ->whereIn('station_id', $stationIds)
            ->where('status', 'completed')
            ->latest('transacted_at')
            ->limit(10)
            ->with(['station', 'fuelProduct', 'attendant'])
            ->get();

        // Nozzle grid (dispensing/completed live)
        $nozzleGrid = Nozzle::query()
            ->whereIn('station_id', $stationIds)
            ->whereIn('status', ['dispensing', 'completed', 'idle'])
            ->with(['station', 'pump', 'fuelProduct'])
            ->limit(12)
            ->get();

        // Tank grid
        $tankGrid = Tank::query()
            ->whereIn('station_id', $stationIds)
            ->with(['station', 'fuelProduct'])
            ->limit(8)
            ->get();

        // Deliveries upcoming
        $deliveryList = FuelDelivery::query()
            ->whereIn('station_id', $stationIds)
            ->whereIn('status', ['scheduled', 'in_transit', 'completed'])
            ->latest('delivery_date')
            ->with(['station', 'supplier'])
            ->limit(8)
            ->get();

        // Alerts
        $alertList = Alert::query()
            ->whereIn('station_id', $stationIds)
            ->unresolved()
            ->latest()
            ->limit(6)
            ->with('station')
            ->get();

        // Station status list (counts already computed)
        $avgTransaction = $salesToday->tx_count > 0 ? round($salesToday->revenue / $salesToday->tx_count, 0) : 0;

        return [
            'totalStations' => count($stationIds),
            'stationsOnline' => $stationCounts['online'] ?? 0,
            'stationsWarning' => $stationCounts['warning'] ?? 0,
            'stationsMaintenance' => $stationCounts['maintenance'] ?? 0,
            'stationsOffline' => $stationCounts['offline'] ?? 0,
            'todayRevenue' => (float) $salesToday->revenue,
            'todayLitres' => (float) $salesToday->litres,
            'revenueChange' => $revenueChange,
            'fuelSoldPetrol' => round((float) $fuelSoldPetrol, 2),
            'fuelSoldDiesel' => round((float) $fuelSoldDiesel, 2),
            'pumpsOnline' => $pumpsOnline,
            'pumpsTotal' => $pumpsTotal,
            'pumpsActivePct' => $pumpsActivePct,
            'nozzlesOnline' => $nozzlesOnline,
            'nozzlesTotal' => $nozzlesTotal,
            'inventoryLitres' => round((float) $inventoryTotal, 3),
            'inventoryYesterday' => round((float) $inventoryYesterday, 3),
            'inventoryChange' => $inventoryChange,
            'pendingDeliveries' => $pendingDeliveries,
            'todayTransactions' => (int) $salesToday->tx_count,
            'activeAlerts' => $activeAlerts,
            'criticalAlerts' => $criticalAlerts,
            'avgTransaction' => (float) $avgTransaction,
            'dailyChart' => $dailyChart,
            'paymentSummary' => $paymentSummary,
            'topStations' => $topStations,
            'recentTransactions' => $recentTransactions,
            'nozzleGrid' => $nozzleGrid,
            'tankGrid' => $tankGrid,
            'deliveryList' => $deliveryList,
            'alertList' => $alertList,
        ];
    }

    /**
     * Per-station snapshot for the station detail/dashboard page.
     */
    public function station(Station $station): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $stats = FuelTransaction::where('station_id', $station->id)
            ->whereDate('transacted_at', now()->toDateString())
            ->where('status', 'completed')
            ->selectRaw('count(*) as tx_count, COALESCE(SUM(net_amount),0) as revenue, COALESCE(SUM(litres),0) as litres')
            ->first();

        return [
            'station' => $station,
            'pumps' => $station->pumps()->withCount('nozzles')->get(),
            'nozzles' => $station->nozzles()->with('pump', 'fuelProduct')->get(),
            'tanks' => $station->tanks()->with('fuelProduct')->get(),
            'todayRevenue' => (float) $stats->revenue,
            'todayLitres' => (float) $stats->litres,
            'todayTransactions' => (int) $stats->tx_count,
            'transactions' => $station->transactions()->latest('transacted_at')->limit(10)->with('fuelProduct', 'attendant')->get(),
            'deliveries' => $station->deliveries()->latest()->limit(5)->with('supplier')->get(),
            'alerts' => $station->alerts()->unresolved()->latest()->get(),
        ];
    }
}