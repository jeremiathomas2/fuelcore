<?php

namespace App\Http\Controllers;

use App\Models\IntegrationTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = IntegrationTransaction::query()
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->filled('search'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('transaction_uuid', 'like', "%{$s}%")
                ->orWhere('station_code', 'like', "%{$s}%")
                ->orWhere('error_message', 'like', "%{$s}%")))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        $counts = IntegrationTransaction::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('integrations.index', [
            'transactions' => $transactions,
            'counts' => $counts,
        ]);
    }

    public function retry(Request $request, IntegrationTransaction $integrationTransaction): RedirectResponse
    {
        $payload = is_array($integrationTransaction->payload) ? $integrationTransaction->payload : json_decode($integrationTransaction->payload, true);

        $result = app(\App\Services\IntegrationService::class)->ingestFuelTransaction($payload ?? []);

        return back()->with(
            $result['status'] === 'processed' ? 'success' : 'error',
            $result['status'] === 'processed'
                ? "Payload reprocessed as transaction #{$result['transaction_id']}."
                : 'Reprocessing failed: ' . ($result['error'] ?? 'unknown error'),
        );
    }
}