@extends('layouts.app')

@section('active', 'customers')
@section('page_title', $customer->name)
@section('page_breadcrumb', 'FUELCORE / Customers / '.$customer->customer_number)

@section('content')
  <div class="page-head">
    <div>
      <h1>{{ $customer->name }}</h1>
      <p>{{ $customer->customer_number }} · {{ ucfirst($customer->type) }} customer</p>
    </div>
    <div class="page-actions">
      @can('customer.manage')
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary"><i data-lucide="pencil"></i> Edit</a>
      @endcan
      <a href="{{ route('customers.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  @include('partials.flash')

  <section class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon teal"><i data-lucide="receipt"></i></div></div>
      <div class="kpi-value">{{ $transactions->count() }}</div>
      <div class="kpi-label">Recent Transactions</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon green"><i data-lucide="banknote"></i></div></div>
      <div class="kpi-value">{{ currency() }} {{ number_format((float) $transactions->sum('net_amount'), 0) }}</div>
      <div class="kpi-label">Volume (recent)</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon amber"><i data-lucide="wallet"></i></div></div>
      <div class="kpi-value">{{ currency() }} {{ number_format((float) $customer->outstanding_balance, 0) }}</div>
      <div class="kpi-label">Outstanding Balance</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon {{ $customer->status === 'active' ? 'green' : 'red' }}"><i data-lucide="shield-check"></i></div></div>
      <div class="kpi-value">{{ ucfirst($customer->status) }}</div>
      <div class="kpi-label">Status</div>
    </div>
  </section>

  <section class="two-col">
    <div class="card">
      <div class="card-header"><h3><i data-lucide="user"></i> Contact Details</h3></div>
      <div class="card-body">
        <div class="detail-grid">
          <div class="detail-item"><div class="dt">Phone</div><div class="dd">{{ $customer->phone ?? '—' }}</div></div>
          <div class="detail-item"><div class="dt">Email</div><div class="dd">{{ $customer->email ?? '—' }}</div></div>
          <div class="detail-item"><div class="dt">Address</div><div class="dd">{{ $customer->address ?? '—' }}</div></div>
          <div class="detail-item"><div class="dt">Tax ID</div><div class="dd">{{ $customer->tax_id ?? '—' }}</div></div>
          <div class="detail-item"><div class="dt">Credit Limit</div><div class="dd">{{ currency() }} {{ number_format((float) $customer->credit_limit, 0) }}</div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i data-lucide="car"></i> Vehicles</h3></div>
      <div class="card-body flush">
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Registration</th><th>Driver</th><th>Phone</th><th>Fleet Account</th></tr></thead>
            <tbody>
              @forelse ($vehicles as $v)
                <tr>
                  <td class="bold">{{ $v->registration_number }}</td>
                  <td>{{ $v->driver_name ?? '—' }}</td>
                  <td class="muted small">{{ $v->driver_phone ?? '—' }}</td>
                  <td class="muted small">{{ $v->fleetAccount?->company_name ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="4"><div class="empty-state">No vehicles registered.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3><i data-lucide="truck"></i> Fleet Accounts</h3></div>
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Account No</th><th>Company</th><th class="numeric">Credit Limit</th><th class="numeric">Fuel Limit (L)</th><th>Status</th></tr></thead>
          <tbody>
            @forelse ($fleetAccounts as $fa)
              <tr onclick="window.location='{{ route('fleet.show', $fa) }}'">
                <td class="mono">{{ $fa->account_number }}</td>
                <td class="bold">{{ $fa->company_name }}</td>
                <td class="numeric">{{ currency() }} {{ number_format((float) $fa->credit_limit, 0) }}</td>
                <td class="numeric">{{ $fa->fuel_limit_litres ? number_format((float) $fa->fuel_limit_litres, 0) : '—' }}</td>
                <td><span class="status-pill-sm {{ $fa->status === 'active' ? 'online' : 'warning' }}">{{ $fa->status }}</span></td>
              </tr>
            @empty
              <tr><td colspan="5"><div class="empty-state">No fleet accounts.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3><i data-lucide="receipt"></i> Recent Transactions</h3></div>
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Receipt</th><th>Station</th><th>Fuel</th><th class="numeric">Litres</th><th class="numeric">Amount</th><th>Time</th></tr></thead>
          <tbody>
            @forelse ($transactions as $t)
              <tr>
                <td class="mono">{{ $t->transaction_number }}</td>
                <td>{{ $t->station?->name }}</td>
                <td>{{ $t->fuelProduct?->name }}</td>
                <td class="numeric">{{ number_format((float) $t->litres, 2) }} L</td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float) $t->net_amount, 0) }}</td>
                <td class="muted small">{{ $t->transacted_at->format('d M Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="6"><div class="empty-state">No transactions yet.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endsection