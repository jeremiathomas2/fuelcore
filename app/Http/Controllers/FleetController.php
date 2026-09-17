<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\FleetAccount;
use App\Models\FleetVehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FleetController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = FleetAccount::query()
            ->with('customer')
            ->withCount('vehicles')
            ->when($request->filled('search'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('company_name', 'like', "%{$s}%")
                ->orWhere('account_number', 'like', "%{$s}%")))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('fleet.index', ['accounts' => $accounts]);
    }

    public function create(): View
    {
        return view('fleet.create', [
            'customers' => Customer::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $vehicleData = $data['vehicles'] ?? [];
        unset($data['vehicles']);

        $account = FleetAccount::create([
            ...$data,
            'account_number' => FleetAccount::generateAccountNumber(),
        ]);

        AuditLog::record([
            'action' => 'create',
            'module' => 'fleet_accounts',
            'record_id' => $account->id,
            'description' => "Fleet account created for {$account->company_name}",
            'new_values' => $account->only(['account_number', 'company_name', 'credit_limit']),
        ]);

        foreach ($vehicleData as $vehicle) {
            if (empty($vehicle['registration_number'])) {
                continue;
            }
            FleetVehicle::create([
                'fleet_account_id' => $account->id,
                'customer_id' => $account->customer_id,
                'registration_number' => $vehicle['registration_number'],
                'driver_name' => $vehicle['driver_name'] ?? null,
                'driver_phone' => $vehicle['driver_phone'] ?? null,
                'fuel_limit_litres' => $vehicle['fuel_limit_litres'] ?? null,
            ]);
        }

        return redirect()->route('fleet.index')->with('success', 'Fleet account created.');
    }

    public function show(FleetAccount $fleet): View
    {
        return view('fleet.show', [
            'account' => $fleet,
            'vehicles' => $fleet->vehicles()->latest()->get(),
            'transactions' => $fleet->transactions()
                ->with('station', 'fuelProduct', 'vehicle')
                ->latest('transacted_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function edit(FleetAccount $fleet): View
    {
        return view('fleet.edit', [
            'account' => $fleet,
            'customers' => Customer::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, FleetAccount $fleet): RedirectResponse
    {
        $data = $request->validate($this->rules($fleet->id));

        $vehicleData = $data['vehicles'] ?? null;
        unset($data['vehicles']);

        $fleet->update($data);

        if ($vehicleData !== null) {
            $keptIds = [];

            foreach ($vehicleData as $vehicle) {
                if (empty($vehicle['registration_number'])) {
                    continue;
                }

                $attrs = [
                    'registration_number' => $vehicle['registration_number'],
                    'driver_name' => $vehicle['driver_name'] ?? null,
                    'driver_phone' => $vehicle['driver_phone'] ?? null,
                    'fuel_limit_litres' => $vehicle['fuel_limit_litres'] ?? null,
                ];

                $existing = ! empty($vehicle['id'])
                    ? $fleet->vehicles()->whereKey($vehicle['id'])->first()
                    : null;

                if ($existing) {
                    $existing->update($attrs);
                    $keptIds[] = $existing->id;
                } else {
                    $keptIds[] = $fleet->vehicles()->create([...$attrs, 'customer_id' => $fleet->customer_id])->id;
                }
            }

            $fleet->vehicles()->whereNotIn('id', $keptIds)->delete();
        }

        AuditLog::record([
            'action' => 'update',
            'module' => 'fleet_accounts',
            'record_id' => $fleet->id,
            'description' => "Fleet account updated: {$fleet->company_name}",
            'new_values' => $fleet->only(['company_name', 'status']),
        ]);

        return redirect()->route('fleet.show', $fleet)->with('success', 'Fleet account updated.');
    }

    public function toggleStatus(Request $request, FleetAccount $fleet): RedirectResponse
    {
        $newStatus = $fleet->status === 'active' ? 'inactive' : 'active';
        $oldStatus = $fleet->status;
        $fleet->update(['status' => $newStatus]);

        AuditLog::record([
            'action' => $newStatus === 'active' ? 'activate' : 'archive',
            'module' => 'fleet_accounts',
            'record_id' => $fleet->id,
            'description' => "Fleet account {$newStatus}: {$fleet->company_name}",
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
        ]);

        return back()->with('success', "Fleet account {$fleet->company_name} is now {$newStatus}.");
    }

    protected function rules(?int $ignore = null): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'fuel_limit_litres' => ['nullable', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'monthly_statement' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'blocked'])],
            'vehicles' => ['sometimes', 'array'],
            'vehicles.*.id' => ['nullable', 'integer', 'exists:fleet_vehicles,id'],
            'vehicles.*.registration_number' => ['nullable', 'string', 'max:20'],
            'vehicles.*.driver_name' => ['nullable', 'string', 'max:255'],
            'vehicles.*.driver_phone' => ['nullable', 'string', 'max:20'],
            'vehicles.*.fuel_limit_litres' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}