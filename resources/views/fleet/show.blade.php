@extends('layouts.app')

@section('active', 'fleet')
@section('page_title', $account->company_name)
@section('page_breadcrumb', 'FUELCORE / Fleet / '.$account->account_number)

@section('content')
  <div class="page-head">
    <div><h1>{{ $account->company_name }}</h1><p>{{ $account->account_number }} · {{ $account->contact_person ?? '' }} {{ $account->phone ? '· '.$account->phone : '' }}</p></div>
    <div class="page-actions">
      @can('fleet.manage')
        <a href="{{ route('fleet.edit', $account) }}" class="btn btn-primary"><i data-lucide="pencil"></i> Edit</a>
      @endcan
      <a href="{{ route('fleet.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  @include('partials.flash')

  <section class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon blue"><i data-lucide="car"></i></div></div>
      <div class="kpi-value">{{ $vehicles->count() }}</div>
      <div class="kpi-label">Vehicles</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon green"><i data-lucide="banknote"></i></div></div>
      <div class="kpi-value">{{ currency() }} {{ number_format((float) $account->credit_limit, 0) }}</div>
      <div class="kpi-label">Credit Limit</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon amber"><i data-lucide="droplets"></i></div></div>
      <div class="kpi-value">{{ $account->fuel_limit_litres ? number_format((float) $account->fuel_limit_litres, 0).' L' : 'Unlimited' }}</div>
      <div class="kpi-label">Fuel Limit</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon {{ $account->status === 'active' ? 'green' : 'red' }}"><i data-lucide="shield-check"></i></div></div>
      <div class="kpi-value">{{ ucfirst($account->status) }}</div>
      <div class="kpi-label">Status</div>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3><i data-lucide="car"></i> Vehicles</h3></div>
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Registration</th><th>Driver</th><th>Phone</th><th>Fuel Limit</th><th>Status</th></tr></thead>
          <tbody>
            @forelse ($vehicles as $v)
              <tr>
                <td class="bold mono">{{ $v->registration_number }}</td>
                <td>{{ $v->driver_name ?? '—' }}</td>
                <td class="muted small">{{ $v->driver_phone ?? '—' }}</td>
                <td class="numeric">{{ $v->fuel_limit_litres ? number_format((float) $v->fuel_limit_litres, 0).' L' : '—' }}</td>
                <td><span class="status-pill-sm {{ $v->status === 'active' ? 'online' : 'warning' }}">{{ $v->status }}</span></td>
              </tr>
            @empty
              <tr><td colspan="5"><div class="empty-state">No vehicles registered.</div></td></tr>
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
          <thead><tr><th>Receipt</th><th>Station</th><th>Fuel</th><th class="numeric">Litres</th><th class="numeric">Amount</th><th>Vehicle</th><th>Time</th></tr></thead>
          <tbody>
            @forelse ($transactions as $t)
              <tr>
                <td class="mono">{{ $t->transaction_number }}</td>
                <td>{{ $t->station?->name }}</td>
                <td>{{ $t->fuelProduct?->name }}</td>
                <td class="numeric">{{ number_format((float) $t->litres, 2) }} L</td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float) $t->net_amount, 0) }}</td>
                <td class="muted small">{{ $t->vehicle?->registration_number ?? '—' }}</td>
                <td class="muted small">{{ $t->transacted_at->format('d M Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="7"><div class="empty-state">No transactions yet.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endsection