<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $payments = Payment::query()
            ->with(['transaction.station', 'transaction.fuelProduct', 'createdBy'])
            ->whereHas('transaction', fn ($q) => $q->whereIn('station_id', $this->visibleStations($user)->pluck('id')))
            ->when($request->filled('method'), fn ($q, $v) => $q->where('method', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->filled('from'), fn ($q, $v) => $q->whereDate('created_at', '>=', \Illuminate\Support\Carbon::parse($v)))
            ->when($request->filled('to'), fn ($q, $v) => $q->whereDate('created_at', '<=', \Illuminate\Support\Carbon::parse($v)))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        $totals = Payment::query()
            ->whereHas('transaction', fn ($q) => $q->whereIn('station_id', $this->visibleStations($user)->pluck('id')))
            ->where('status', 'paid')
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        return view('payments.index', [
            'payments' => $payments,
            'totals' => $totals,
        ]);
    }

    public function create(Request $request): View
    {
        return view('payments.create', [
            'customers' => Customer::where('status', 'active')
                ->where('outstanding_balance', '>', 0)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'reference' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = Customer::findOrFail($data['customer_id']);

        if ((float) $data['amount'] > (float) $customer->outstanding_balance) {
            return back()->withErrors(['amount' => 'Amount exceeds the outstanding balance of ' . number_format((float) $customer->outstanding_balance, 2) . '.']);
        }

        $payment = Payment::create([
            'payment_number' => 'PMT-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'provider' => $data['provider'] ?? null,
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        $customer->decrement('outstanding_balance', $data['amount']);

        \App\Models\AuditLog::record([
            'action' => 'create',
            'module' => 'payments',
            'record_id' => $payment->id,
            'description' => "Payment of " . number_format((float) $payment->amount, 2) . " received from {$customer->name}",
            'new_values' => ['amount' => $payment->amount, 'method' => $payment->method],
        ]);

        return redirect()->route('payments.index')->with('success', 'Payment recorded.');
    }
}