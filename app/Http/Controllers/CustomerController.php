<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\FleetAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount('transactions')
            ->withSum('transactions as revenue', 'net_amount')
            ->when($request->filled('search'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('customer_number', 'like', "%{$s}%")))
            ->when($request->filled('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('customers.index', ['customers' => $customers]);
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $customer = Customer::create([
            ...$data,
            'customer_number' => Customer::generateCustomerNumber(),
        ]);

        AuditLog::record([
            'action' => 'create',
            'module' => 'customers',
            'record_id' => $customer->id,
            'description' => "Customer created: {$customer->name} ({$customer->customer_number})",
            'new_values' => $customer->only(['customer_number', 'name', 'type']),
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        return view('customers.show', [
            'customer' => $customer,
            'vehicles' => $customer->vehicles()->with('fleetAccount')->get(),
            'fleetAccounts' => $customer->fleetAccounts()->latest()->get(),
            'transactions' => $customer->transactions()
                ->with('station', 'fuelProduct', 'payment')
                ->latest('transacted_at')
                ->limit(15)
                ->get(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate($this->rules($customer->id));
        $customer->update($data);

        AuditLog::record([
            'action' => 'update',
            'module' => 'customers',
            'record_id' => $customer->id,
            'description' => "Customer updated: {$customer->name}",
            'new_values' => $customer->only(['name', 'status']),
        ]);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function toggleStatus(Request $request, Customer $customer): RedirectResponse
    {
        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        $oldStatus = $customer->status;
        $customer->update(['status' => $newStatus]);

        AuditLog::record([
            'action' => $newStatus === 'active' ? 'activate' : 'archive',
            'module' => 'customers',
            'record_id' => $customer->id,
            'description' => "Customer {$newStatus}: {$customer->name}",
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
        ]);

        return back()->with('success', "Customer {$customer->name} is now {$newStatus}.");
    }

    protected function rules(?int $ignore = null): array
    {
        return [
            'type' => ['required', Rule::in(['individual', 'corporate', 'fleet', 'government', 'other'])],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'blocked'])],
        ];
    }
}