@extends('layouts.app')

@section('active', 'stations')
@section('page_title', $station->name)
@section('page_breadcrumb', 'FUELCORE / Stations / '.$station->code)

@section('content')
  <div class="page-head">
    <div>
      <h1>{{ $station->name }}</h1>
      <p>{{ $station->code }} · {{ $station->location }}, {{ $station->region }}</p>
    </div>
    <div class="page-actions">
      @can('station.manage')
        <a href="{{ route('stations.edit', $station) }}" class="btn btn-primary"><i data-lucide="pencil"></i> Edit</a>
      @endcan
      <a href="{{ route('stations.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  @include('partials.flash')

  @php($inventoryLitres = $tanks->sum('current_volume'))

  <section class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon blue"><i data-lucide="fuel"></i></div></div>
      <div class="kpi-value">{{ $pumps->count() }}</div>
      <div class="kpi-label">Pumps</div>
      <div class="kpi-secondary">{{ $pumps->where('status', 'online')->count() }} online</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon teal"><i data-lucide="droplets"></i></div></div>
      <div class="kpi-value">{{ number_format((float) $todayLitres, 0) }} L</div>
      <div class="kpi-label">Sold Today</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon green"><i data-lucide="banknote"></i></div></div>
      <div class="kpi-value">{{ currency() }} {{ number_format((float) $todayRevenue, 0) }}</div>
      <div class="kpi-label">Revenue Today</div>
      <div class="kpi-secondary">{{ $todayTransactions }} transactions</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon amber"><i data-lucide="package"></i></div></div>
      <div class="kpi-value">{{ number_format((float) $inventoryLitres, 0) }} L</div>
      <div class="kpi-label">Current Inventory</div>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3><i data-lucide="info"></i> Station Details</h3></div>
    <div class="card-body">
      <div class="detail-grid">
        <div class="detail-item"><div class="dt">Status</div><div class="dd"><span class="status-pill-sm {{ $station->status === 'online' ? 'online' : 'warning' }}">{{ $station->status }}</span></div></div>
        <div class="detail-item"><div class="dt">Manager</div><div class="dd">{{ $station->manager_name ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Phone</div><div class="dd">{{ $station->phone ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Email</div><div class="dd">{{ $station->email ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Coordinates</div><div class="dd">{{ $station->coordinates ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Max Pump Capacity</div><div class="dd">{{ $station->max_pump_capacity ? number_format($station->max_pump_capacity, 2).' L/h' : '—' }}</div></div>
        <div class="detail-item"><div class="dt">Last Sync</div><div class="dd">{{ $station->last_sync_at?->format('d M Y H:i') ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Registered</div><div class="dd">{{ $station->created_at->format('d M Y') }}</div></div>
      </div>
    </div>
  </section>

  <section class="two-col">
    <div class="card">
      <div class="card-header"><h3><i data-lucide="fuel"></i> Pumps</h3></div>
      <div class="card-body flush">
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Pump</th><th>Type</th><th>Status</th><th>Nozzles</th></tr></thead>
            <tbody>
              @forelse ($pumps as $pump)
                <tr>
                  <td class="bold">Pump {{ $pump->pump_number }}</td>
                  <td class="muted small">{{ ucfirst($pump->pump_type) }}</td>
                  <td><span class="status-pill-sm {{ $pump->status === 'online' ? 'online' : 'warning' }}">{{ $pump->status }}</span></td>
                  <td class="numeric">{{ $pump->nozzles_count }}</td>
                </tr>
              @empty
                <tr><td colspan="4"><div class="empty-state">No pumps.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i data-lucide="database"></i> Tanks</h3></div>
      <div class="card-body flush">
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Tank</th><th>Fuel</th><th class="numeric">Volume</th><th class="numeric">Capacity</th><th>Level</th></tr></thead>
            <tbody>
              @forelse ($tanks as $tank)
                <tr>
                  <td class="bold">{{ $tank->tank_number }}</td>
                  <td>{{ $tank->fuelProduct?->name }}</td>
                  <td class="numeric">{{ number_format((float) $tank->current_volume, 0) }} L</td>
                  <td class="numeric">{{ number_format((float) $tank->capacity, 0) }} L</td>
                  <td>
                    <div class="stock-bar" style="width:120px;"><div class="stock-bar-fill {{ $tank->levelPercent() < 15 ? 'low' : ($tank->levelPercent() < 50 ? 'mid' : 'good') }}" style="width:{{ $tank->levelPercent() }}%;"></div></div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5"><div class="empty-state">No tanks.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-header">
      <h3><i data-lucide="receipt"></i> Recent Transactions</h3>
      <div class="section-actions"><a class="btn-sm" href="{{ route('sales.index', ['station' => $station->id]) }}"><i data-lucide="arrow-right"></i> View all</a></div>
    </div>
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Receipt</th><th>Fuel</th><th class="numeric">Litres</th><th class="numeric">Amount</th><th>Time</th><th>Status</th></tr></thead>
          <tbody>
            @forelse ($transactions as $t)
              <tr>
                <td class="mono">{{ $t->transaction_number }}</td>
                <td>{{ $t->fuelProduct?->name }}</td>
                <td class="numeric">{{ number_format((float) $t->litres, 2) }} L</td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float) $t->net_amount, 0) }}</td>
                <td class="muted small">{{ $t->transacted_at->format('d M Y H:i') }}</td>
                <td><span class="status-pill-sm online">{{ $t->status }}</span></td>
              </tr>
            @empty
              <tr><td colspan="6"><div class="empty-state">No transactions for this station.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endsection