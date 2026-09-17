@extends('layouts.app')

@section('active', 'dashboard')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_breadcrumb', 'FUELCORE / Dashboard')

@section('content')
  <!-- HERO -->
  <section class="hero-section">
    <div class="hero-top">
      <div>
        <h1 class="hero-title">Fuel Operations Overview</h1>
        <p class="hero-subtitle">Real-time performance across all filling stations</p>
      </div>
      <div class="live-badge">
        <span class="dot"></span>
        Live Monitoring ON
      </div>
    </div>
    <div class="hero-meta">
      <div class="hero-meta-item">
        <i data-lucide="clock"></i>
        <span id="heroTime">{{ now()->format('d M Y, H:i:s') }}</span>
      </div>
      <div class="hero-meta-item">
        <i data-lucide="refresh-cw"></i>
        <span>Last sync: {{ now()->format('H:i:s') }}</span>
      </div>
      <div class="hero-meta-item">
        <i data-lucide="wifi"></i>
        <span style="color:#6EE7B7;">System connected · {{ $totalStations }} stations</span>
      </div>
    </div>
  </section>

  <!-- KPI GRID -->
  <section class="kpi-grid">
    @php
        $fmt = fn ($v) => number_format((float) $v, 0);
    @endphp

    <div class="kpi-card">
      <div class="kpi-header">
        <div class="kpi-icon blue"><i data-lucide="building-2"></i></div>
        <span class="kpi-trend up">+{{ $stationsOnline }}</span>
      </div>
      <div class="kpi-value">{{ $totalStations }}</div>
      <div class="kpi-label">Total Stations</div>
      <div class="kpi-secondary">
        <span class="status-dot green"></span> {{ $stationsOnline }} Online
        <span class="sep">·</span>
        <span class="status-dot amber"></span> {{ $stationsWarning }} Warning
        <span class="sep">·</span>
        <span class="status-dot red"></span> {{ $stationsOffline }} Offline
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <div class="kpi-icon green"><i data-lucide="banknote"></i></div>
        <span class="kpi-trend {{ $revenueChange >= 0 ? 'up' : 'down' }}">{{ $revenueChange >= 0 ? '+' : '' }}{{ $revenueChange }}%</span>
      </div>
      <div class="kpi-value">{{ currency() }} {{ $fmt($todayRevenue) }}</div>
      <div class="kpi-label">Today's Fuel Sales</div>
      <div class="kpi-secondary">vs yesterday</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <div class="kpi-icon teal"><i data-lucide="droplets"></i></div>
        <span class="kpi-trend up">+{{ $fmt($todayLitres) }}</span>
      </div>
      <div class="kpi-value">{{ $fmt($todayLitres) }} L</div>
      <div class="kpi-label">Fuel Sold Today</div>
      <div class="kpi-secondary">Petrol {{ $fmt($fuelSoldPetrol) }} L · Diesel {{ $fmt($fuelSoldDiesel) }} L</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <div class="kpi-icon purple"><i data-lucide="fuel"></i></div>
        <span class="kpi-trend up">{{ $pumpsActivePct }}%</span>
      </div>
      <div class="kpi-value">{{ $pumpsOnline }} / {{ $pumpsTotal }}</div>
      <div class="kpi-label">Active Pumps</div>
      <div class="kpi-secondary">utilization rate</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <div class="kpi-icon amber"><i data-lucide="package"></i></div>
        <span class="kpi-trend {{ $inventoryChange < 0 ? 'down' : 'up' }}">{{ $inventoryChange > 0 ? '+' : '' }}{{ $inventoryChange }}%</span>
      </div>
      <div class="kpi-value">{{ $fmt($inventoryLitres) }} L</div>
      <div class="kpi-label">Current Inventory</div>
      <div class="kpi-secondary">vs {{ $fmt($inventoryYesterday) }} L yesterday</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <div class="kpi-icon blue"><i data-lucide="truck"></i></div>
        <span class="kpi-trend up">+{{ $pendingDeliveries }}</span>
      </div>
      <div class="kpi-value">{{ $pendingDeliveries }}</div>
      <div class="kpi-label">Pending Deliveries</div>
      <div class="kpi-secondary">scheduled / in transit</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <div class="kpi-icon teal"><i data-lucide="receipt"></i></div>
        <span class="kpi-trend up">{{ $todayTransactions }}</span>
      </div>
      <div class="kpi-value" data-counter="{{ $todayTransactions }}">{{ $todayTransactions }}</div>
      <div class="kpi-label">Transactions Today</div>
      <div class="kpi-secondary">Avg {{ currency() }} {{ $fmt($avgTransaction) }}</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-header">
        <div class="kpi-icon red"><i data-lucide="alert-triangle"></i></div>
        <span class="kpi-trend down">{{ $criticalAlerts }} critical</span>
      </div>
      <div class="kpi-value">{{ $activeAlerts }}</div>
      <div class="kpi-label">Active Alerts</div>
      <div class="kpi-secondary">require attention</div>
    </div>
  </section>

  <!-- STATION FILTER -->
  <section class="filter-bar">
    <form action="{{ route('dashboard') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-family:inherit;font-size:0.75rem;color:var(--text-dark);cursor:pointer;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ $selectedStation?->id === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn-sm primary"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('dashboard') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <!-- ALL STATIONS + STATUS -->
  <section class="two-col">
    <div class="card">
      <div class="card-header">
        <h3><i data-lucide="building-2"></i> Top Stations Today</h3>
        <div class="section-actions">
          <a class="btn-sm" href="{{ route('stations.index') }}"><i data-lucide="arrow-right"></i> Manage</a>
        </div>
      </div>
      <div class="card-body flush">
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Station</th>
                <th class="numeric">Litres</th>
                <th class="numeric">Revenue</th>
                <th class="numeric">Transactions</th>
                <th>Last Sync</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topStations as $row)
                <tr onclick="window.location='{{ route('stations.show', $row->station) }}'">
                  <td>
                    <span class="row-avatar">{{ strtoupper(substr($row->station->code, 0, 2)) }}</span>
                    <strong>{{ $row->station->name }}</strong>
                    <span class="muted small"> · {{ $row->station->region }}</span>
                  </td>
                  <td class="numeric bold">{{ number_format((float) $row->litres, 0) }} L</td>
                  <td class="numeric bold">{{ currency() }} {{ number_format((float) $row->revenue, 0) }}</td>
                  <td class="numeric">{{ $row->tx_count }}</td>
                  <td class="muted small">{{ $row->station->updated_at->diffForHumans() }}</td>
                </tr>
              @empty
                <tr><td colspan="5"><div class="empty-state">No sales recorded today yet.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3><i data-lucide="activity"></i> Station Status</h3>
      </div>
      <div class="card-body">
        <div class="station-status-list">
          <div class="station-status-item">
            <span class="label"><span class="status-dot green" style="width:8px;height:8px;"></span> Online</span>
            <span class="count">{{ $stationsOnline }}</span>
          </div>
          <div class="station-status-item">
            <span class="label"><span class="status-dot amber" style="width:8px;height:8px;"></span> Warning</span>
            <span class="count">{{ $stationsWarning }}</span>
          </div>
          <div class="station-status-item">
            <span class="label"><span class="status-dot amber" style="width:8px;height:8px;"></span> Maintenance</span>
            <span class="count">{{ $stationsMaintenance }}</span>
          </div>
          <div class="station-status-item">
            <span class="label"><span class="status-dot red" style="width:8px;height:8px;"></span> Offline</span>
            <span class="count">{{ $stationsOffline }}</span>
          </div>
        </div>
        <div style="margin-top:18px;">
          @php
              $paymentLabels = collect($paymentSummary)->keys();
              $paymentValues = collect($paymentSummary)->values();
              $payLabels = $paymentLabels->map(fn ($k) => ucfirst(str_replace('_', ' ', $k)));
          @endphp
          @if ($payLabels->isNotEmpty())
            <div class="chart-container" style="height:180px;margin-bottom:14px;">
              <canvas id="paymentChart"
                data-labels='{{ json_encode($payLabels) }}'
                data-values='{{ json_encode($paymentValues) }}'></canvas>
            </div>
          @endif
          <div class="donut-legend" style="display:flex;flex-direction:column;gap:8px;">
            @foreach ($payLabels as $i => $label)
              <div class="legend-item">
                <span class="swatch" style="background:{{ ['#2389C9','#20B486','#F2A93B','#7C6FF0','#E55353'][$i % 5] }};"></span>
                {{ $label }}
                <span class="val">{{ currency() }} {{ number_format((float) $paymentValues[$i] ?? 0, 0) }}</span>
              </div>
            @endforeach
            @if ($payLabels->isEmpty())
              <div class="empty-state" style="padding:14px;">No payments recorded today.</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SALES CHARTS -->
  <section class="two-col-even">
    <div class="card">
      <div class="card-header">
        <h3><i data-lucide="trending-up"></i> Revenue — Last 7 Days</h3>
      </div>
      <div class="card-body">
        <div class="chart-container" style="height:260px;">
          <canvas id="salesChart"
            data-labels='{{ json_encode(array_keys($dailyChart)) }}'
            data-values='{{ json_encode(array_values($dailyChart)) }}'></canvas>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <h3><i data-lucide="bar-chart-3"></i> Top Stations by Revenue</h3>
      </div>
      <div class="card-body">
        <div class="chart-container" style="height:260px;">
          <canvas id="stationChart"
            data-labels='{{ json_encode($topStations->take(6)->map(fn ($r) => $r->station->code)) }}'
            data-values='{{ json_encode($topStations->take(6)->map(fn ($r) => $r->revenue)) }}'></canvas>
        </div>
      </div>
    </div>
  </section>

  <!-- NOZZLE GRID -->
  <section class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <h3><i data-lucide="radio"></i> Live Nozzle Status</h3>
      <div class="section-actions">
        <span class="status-pill-sm online"><span class="status-dot green"></span> {{ $nozzlesOnline }}/{{ $nozzlesTotal }} nozzles</span>
      </div>
    </div>
    <div class="card-body">
      <div class="nozzle-grid">
        @forelse ($nozzleGrid as $nozzle)
          <div class="nozzle-card">
            <div class="nozzle-header">
              <span class="nozzle-id">{{ $nozzle->pump?->pump_number }} · {{ $nozzle->nozzle_number }}</span>
              <span class="nozzle-status {{ $nozzle->status }}"><span class="status-dot"></span>{{ $nozzle->status }}</span>
            </div>
            <div class="nozzle-values">
              <div class="nozzle-value-item">
                <span class="val">{{ number_format((float) $nozzle->fuelProduct?->price, 0) }}</span>
                <span class="label">{{ currency() }}/L</span>
              </div>
              <div class="nozzle-value-item">
                <span class="val">{{ number_format((float) $nozzle->meter_current, 2) }}</span>
                <span class="label">Meter</span>
              </div>
            </div>
            <div class="nozzle-status-row">
              <span class="fuel-badge petrol">{{ $nozzle->fuelProduct?->name }}</span>
              <span class="muted small">{{ $nozzle->station?->code }}</span>
            </div>
          </div>
        @empty
          <div class="empty-state" style="grid-column:1/-1;">No nozzles reporting.</div>
        @endforelse
      </div>
    </div>
  </section>

  <!-- TANKS + DELIVERIES + ALERTS -->
  <section class="two-col">
    <div class="card">
      <div class="card-header">
        <h3><i data-lucide="database"></i> Tank Levels</h3>
        <div class="section-actions">
          <a class="btn-sm" href="{{ route('tanks.index') }}"><i data-lucide="arrow-right"></i> View all</a>
        </div>
      </div>
      <div class="card-body">
        <div class="tank-mini" style="display:grid;gap:8px;">
          @forelse ($tankGrid as $tank)
            @php
                $pct = $tank->levelPercent();
                $barClass = $pct < 15 ? 'low' : ($pct < 35 ? 'low' : ($pct < 50 ? 'mid' : 'good'));
            @endphp
            <div class="d-flex" style="align-items:center;gap:12px;">
              <div style="width:145px;flex-shrink:0;">
                <div class="tank-mini-label">
                  {{ $tank->station->code }} · {{ $tank->tank_number }}
                  <span class="tank-pct">{{ $pct }}%</span>
                </div>
              </div>
              <div class="flex-1">
                <div class="stock-bar"><div class="stock-bar-fill {{ $barClass }}" style="width:{{ $pct }}%;"></div></div>
              </div>
              <span class="muted small" style="width:110px;text-align:right;flex-shrink:0;">{{ number_format((float) $tank->current_volume, 0) }} / {{ number_format((float) $tank->capacity, 0) }} L</span>
            </div>
          @empty
            <div class="empty-state">No tanks available.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
      <div class="card-header">
        <h3><i data-lucide="truck"></i> Upcoming Deliveries</h3>
        <div class="section-actions">
          <a class="btn-sm" href="{{ route('deliveries.index') }}"><i data-lucide="arrow-right"></i> View all</a>
        </div>
      </div>
      <div class="card-body flush">
        <div class="alert-list">
          @forelse ($deliveryList as $delivery)
            @php
                $statusColor = match ($delivery->status) { 'completed', 'reconciled' => 'success', 'scheduled' => 'info', 'in_transit', 'receiving' => 'warning', 'cancelled', 'disputed' => 'danger', default => 'neutral' };
            @endphp
            <div class="alert-item">
              <div class="alert-icon info" style="width:28px;height:28px;"><i data-lucide="truck" style="width:13px;height:13px;"></i></div>
              <div class="alert-content">
                <div class="alert-meta" style="font-size:0.74rem;font-weight:700;color:var(--text-dark);">{{ $delivery->delivery_number }} · {{ number_format((float) $delivery->ordered_qty, 0) }} L</div>
                <div class="alert-message" style="font-size:0.65rem;color:var(--text-muted);">{{ $delivery->station?->name }} · {{ $delivery->delivery_date->format('d M Y, H:i') }}</div>
              </div>
              <span class="badge badge-{{ $statusColor }}">{{ $delivery->status }}</span>
            </div>
          @empty
            <div class="empty-state">No upcoming deliveries.</div>
          @endforelse
        </div>
      </div>
    </div>
  </section>

  <!-- RECENT TRANSACTIONS + ALERTS -->
  <section class="card">
    <div class="card-header">
      <h3><i data-lucide="receipt"></i> Recent Transactions</h3>
      <div class="section-actions">
        <a class="btn-sm" href="{{ route('sales.index') }}"><i data-lucide="arrow-right"></i> View all</a>
      </div>
    </div>
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Receipt</th>
              <th>Station</th>
              <th>Fuel</th>
              <th class="numeric">Litres</th>
              <th class="numeric">Amount</th>
              <th>Payment</th>
              <th>Attendant</th>
              <th>Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($recentTransactions as $txn)
              <tr onclick="window.FcModal.open('txnModal')" data-txn="{{ route('sales.show', $txn) }}">
                <td class="mono">{{ $txn->transaction_number }}</td>
                <td>{{ $txn->station?->code }} <span class="muted small">· {{ $txn->station?->name }}</span></td>
                <td>{{ $txn->fuelProduct?->name }}</td>
                <td class="numeric bold">{{ number_format((float) $txn->litres, 2) }} L</td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float) $txn->net_amount, 0) }}</td>
                <td><span class="badge badge-{{ in_array($txn->payment?->method ?? '', ['cash']) ? 'success' : 'info' }}">{{ ucfirst(str_replace('_', ' ', $txn->payment?->method ?? 'cash')) }}</span></td>
                <td class="muted small">{{ $txn->attendant?->name }}</td>
                <td class="muted small">{{ $txn->transacted_at->format('H:i') }}</td>
                <td><span class="status-pill-sm {{ $txn->status === 'completed' ? 'online' : 'warning' }}">{{ $txn->status }}</span></td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No transactions yet today.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
@endsection

@push('modals')
  <!-- Transaction detail modal -->
  <div class="modal-overlay" id="txnModal">
    <div class="modal">
      <div class="modal-header">
        <h3>Transaction Details</h3>
        <button type="button" class="modal-close" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="modal-body" id="txnModalBody">
        <div class="empty-state"><i data-lucide="loader"></i>Loading…</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm" onclick="window.FcModal.closeAll()">Close</button>
      </div>
    </div>
  </div>
@endpush

@push('scripts')
  @vite('resources/js/charts.js')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('tr[data-txn]').forEach((tr) => {
        tr.addEventListener('click', async () => {
          const url = tr.dataset.txn;
          const body = document.getElementById('txnModalBody');
          if (!url) return;
          try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const t = await res.json();
            const fmt = (v) => Number(v || 0).toLocaleString('en', { maximumFractionDigits: 2 });
            document.getElementById('txnModal').classList.add('open');
            body.innerHTML = `
              <div class="detail-list">
                <div class="detail-item"><div class="dt">Receipt</div><div class="dd mono">${t.transaction_number || ''}</div></div>
                <div class="detail-item"><div class="dt">Station</div><div class="dd">${t.station?.name || ''}</div></div>
                <div class="detail-item"><div class="dt">Fuel</div><div class="dd">${t.fuel_product?.name || ''}</div></div>
                <div class="detail-item"><div class="dt">Litres</div><div class="dd">${fmt(t.litres)} L</div></div>
                <div class="detail-item"><div class="dt">Amount</div><div class="dd">{{ currency() }} ${fmt(t.net_amount)}</div></div>
                <div class="detail-item"><div class="dt">Payment</div><div class="dd">${(t.payment?.method || 'cash')}</div></div>
                <div class="detail-item"><div class="dt">Attendant</div><div class="dd">${t.attendant?.name || '-'}</div></div>
                <div class="detail-item"><div class="dt">Time</div><div class="dd">${t.transacted_at ? new Date(t.transacted_at).toLocaleString() : ''}</div></div>
              </div>`;
          } catch (e) {
            body.innerHTML = '<div class="empty-state">Could not load transaction.</div>';
          }
        });
      });
    });
  </script>
@endpush