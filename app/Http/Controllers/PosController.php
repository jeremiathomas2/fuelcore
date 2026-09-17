<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\FleetAccount;
use App\Models\FuelProduct;
use App\Models\FuelTransaction;
use App\Models\Nozzle;
use App\Models\Shift;
use App\Services\FuelSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Gate::allows('pos.use'), 403);

        $user = $request->user();
        $station = $this->stationContext($user, $request->query('station'));

        $products = FuelProduct::where('active', true)->orderBy('name')->get();
        $nozzles = Nozzle::query()
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($station, fn ($q) => $q->where('station_id', $station->id))
            ->with(['pump', 'fuelProduct'])
            ->where('status', '!=', 'offline')
            ->get();

        $openShift = $station ? Shift::where('station_id', $station->id)
            ->where('status', 'open')
            ->where('employee_id', $user->id)
            ->first() : null;

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $fleetAccounts = FleetAccount::where('status', 'active')->with('customer')->orderBy('company_name')->get();

        return view('pos.index', [
            'station' => $station,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
            'products' => $products,
            'nozzles' => $nozzles,
            'openShift' => $openShift,
            'customers' => $customers,
            'fleetAccounts' => $fleetAccounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Gate::allows('pos.use'), 403);

        $data = $request->validate([
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'fuel_product_id' => ['required', 'integer', 'exists:fuel_products,id'],
            'pump_id' => ['nullable', 'integer', 'exists:pumps,id'],
            'nozzle_id' => ['nullable', 'integer', 'exists:nozzles,id'],
            'fuel_type' => ['required', 'in:volume,amount'],
            'litres' => ['required_if:fuel_type,volume', 'nullable', 'numeric', 'min:0.001'],
            'amount' => ['required_if:fuel_type,amount', 'nullable', 'numeric', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,mobile_money,card,fleet_account,credit'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:50'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'fleet_account_id' => ['nullable', 'integer', 'exists:fleet_accounts,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:customer_vehicles,id'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($request->filled('vehicle_plate') && empty($data['vehicle_id'])) {
            $plate = mb_strtoupper(trim((string) $request->input('vehicle_plate')));
            $vehicle = CustomerVehicle::whereRaw('LOWER(registration_number) = ?', [mb_strtolower($plate)])->first();

            if (! $vehicle && ! empty($data['fleet_account_id'])) {
                $fleet = FleetAccount::find($data['fleet_account_id']);
                if ($fleet) {
                    $vehicle = CustomerVehicle::firstOrCreate(
                        ['registration_number' => $plate],
                        ['customer_id' => $fleet->customer_id, 'fleet_account_id' => $fleet->id, 'status' => 'active'],
                    );
                }
            }

            if ($vehicle) {
                $data['vehicle_id'] = $vehicle->id;
            }
        }

        $station = $data['station_id'];
        $product = FuelProduct::findOrFail($data['fuel_product_id']);

        $taxRate = (float) $product->tax_rate;
        $litres = $data['fuel_type'] === 'amount' ? null : (float) $data['litres'];

        $data['tax'] = $data['fuel_type'] === 'volume'
            ? round((float) $data['litres'] * (float) ($data['price'] ?? $product->price) * $taxRate / 100, 2)
            : round((float) $data['amount'] * $taxRate / 100, 2);

        try {
            $transaction = app(FuelSaleService::class)->create($data, $request->user());

            return redirect()->route('pos', ['station' => url_id($data['station_id'])])
                ->with('success', "Sale {$transaction->transaction_number} completed — " . number_format((float) $transaction->net_amount, 0) . ' ' . currency() . '.')
                ->with('receipt_number', $transaction->transaction_number);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}