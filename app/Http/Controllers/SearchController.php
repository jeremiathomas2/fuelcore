<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FleetAccount;
use App\Models\FuelDelivery;
use App\Models\FuelTransaction;
use App\Models\Station;
use App\Models\Supplier;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(): View
    {
        $query = trim(request()->string('q'));

        $results = [
            'stations' => ['total' => 0, 'rows' => collect()],
            'customers' => ['total' => 0, 'rows' => collect()],
            'fleet' => ['total' => 0, 'rows' => collect()],
            'suppliers' => ['total' => 0, 'rows' => collect()],
            'transactions' => ['total' => 0, 'rows' => collect()],
            'deliveries' => ['total' => 0, 'rows' => collect()],
        ];

        if (strlen($query) >= 2) {
            $user = request()->user();
            $visibleStations = $this->visibleStations($user)->pluck('id');

            $stations = Station::visibleTo($user)
                ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('region', 'like', "%{$query}%"))
                ->limit(8)->get();
            $results['stations'] = ['total' => $stations->count(), 'rows' => $stations];

            $customers = Customer::where(fn ($q) => $q->where('name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%")
                ->orWhere('customer_number', 'like', "%{$query}%"))->limit(8)->get();
            $results['customers'] = ['total' => $customers->count(), 'rows' => $customers];

            $fleets = FleetAccount::where(fn ($q) => $q->where('company_name', 'like', "%{$query}%")
                ->orWhere('account_number', 'like', "%{$query}%"))->limit(8)->get();
            $results['fleet'] = ['total' => $fleets->count(), 'rows' => $fleets];

            $suppliers = Supplier::where(fn ($q) => $q->where('name', 'like', "%{$query}%")
                ->orWhere('code', 'like', "%{$query}%"))->limit(8)->get();
            $results['suppliers'] = ['total' => $suppliers->count(), 'rows' => $suppliers];

            $transactions = FuelTransaction::with('station', 'fuelProduct')
                ->whereIn('station_id', $visibleStations)
                ->where(fn ($q) => $q->where('transaction_number', 'like', "%{$query}%")
                    ->orWhere('uuid', 'like', "%{$query}%"))
                ->latest('transacted_at')
                ->limit(8)->get();
            $results['transactions'] = ['total' => $transactions->count(), 'rows' => $transactions];

            $deliveries = FuelDelivery::with('station', 'supplier')
                ->whereIn('station_id', $visibleStations)
                ->where(fn ($q) => $q->where('delivery_number', 'like', "%{$query}%"))
                ->latest('delivery_date')
                ->limit(8)->get();
            $results['deliveries'] = ['total' => $deliveries->count(), 'rows' => $deliveries];
        }

        return view('search.index', compact('query', 'results'));
    }
}