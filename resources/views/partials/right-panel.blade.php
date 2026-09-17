@php
    $rpRevenue = (float) ($todayRevenue ?? 0);
    $rpLitres = (float) ($todayLitres ?? 0);
    $rpTx = (int) ($todayTransactions ?? 0);
    $rpAvg = (float) ($avgTransaction ?? 0);
    $rpOnline = (int) ($stationsOnline ?? 0);
    $rpWarning = (int) ($stationsWarning ?? 0);
    $rpMaintenance = (int) ($stationsMaintenance ?? 0);
    $rpOffline = (int) ($stationsOffline ?? 0);
    $rpDiesel = (float) ($fuelSoldDiesel ?? 0);
    $rpPetrol = (float) ($fuelSoldPetrol ?? 0);
    $rpMaxFuel = max($rpDiesel, $rpPetrol, 1);
    $rpDieselPct = round($rpDiesel / $rpMaxFuel * 100);
    $rpPetrolPct = round($rpPetrol / $rpMaxFuel * 100);
@endphp
<aside class="right-panel{{ request()->cookie('fc_right_panel') === '0' ? ' closed' : '' }}" id="rightPanel">
  <div class="panel-section">
    <div class="panel-section-title"><i data-lucide="chart-column"></i> Today's Snapshot</div>
    <div class="mini-stat-grid">
      <div class="mini-stat">
        <div class="label">Today's Revenue</div>
        <div class="value">{{ currency() }} {{ number_format($rpRevenue, 0) }}</div>
        <div class="sub text-green">{{ ($revenueChange ?? 0) >= 0 ? '+' : '' }}{{ $revenueChange ?? 0 }}%</div>
      </div>
      <div class="mini-stat">
        <div class="label">Fuel Sold</div>
        <div class="value">{{ number_format($rpLitres, 0) }} L</div>
        <div class="sub">today</div>
      </div>
      <div class="mini-stat">
        <div class="label">Transactions</div>
        <div class="value">{{ number_format($rpTx, 0) }}</div>
        <div class="sub">completed</div>
      </div>
      <div class="mini-stat">
        <div class="label">Avg Transaction</div>
        <div class="value">{{ currency() }} {{ number_format($rpAvg, 0) }}</div>
        <div class="sub">per sale</div>
      </div>
    </div>
  </div>

  <div class="panel-section">
    <div class="panel-section-title"><i data-lucide="activity"></i> Station Status</div>
    <div class="station-status-list">
      <div class="station-status-item">
        <span class="label"><span class="status-dot green" style="width:8px;height:8px;"></span> Online</span>
        <span class="count">{{ $rpOnline }}</span>
      </div>
      <div class="station-status-item">
        <span class="label"><span class="status-dot amber" style="width:8px;height:8px;"></span> Warning</span>
        <span class="count">{{ $rpWarning }}</span>
      </div>
      <div class="station-status-item">
        <span class="label"><span class="status-dot" style="width:8px;height:8px;background:#718096;"></span> Maintenance</span>
        <span class="count">{{ $rpMaintenance }}</span>
      </div>
      <div class="station-status-item">
        <span class="label"><span class="status-dot red" style="width:8px;height:8px;"></span> Offline</span>
        <span class="count">{{ $rpOffline }}</span>
      </div>
    </div>
  </div>

  <div class="panel-section">
    <div class="panel-section-title"><i data-lucide="droplet"></i> Top Selling Fuel</div>
    <div class="fuel-ranking">
      <div class="fuel-rank-item">
        <div style="flex:1;">
          <div class="fuel-rank-label">
            <span class="name">Diesel</span>
            <span class="value">{{ number_format($rpDiesel, 0) }} L</span>
          </div>
          <div class="fuel-rank-bar">
            <div class="fuel-rank-fill diesel" style="width:{{ $rpDieselPct }}%;"></div>
          </div>
        </div>
      </div>
      <div class="fuel-rank-item">
        <div style="flex:1;">
          <div class="fuel-rank-label">
            <span class="name">Petrol</span>
            <span class="value">{{ number_format($rpPetrol, 0) }} L</span>
          </div>
          <div class="fuel-rank-bar">
            <div class="fuel-rank-fill petrol" style="width:{{ $rpPetrolPct }}%;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="panel-section">
    <div class="panel-section-title"><i data-lucide="heart-pulse"></i> System Health</div>
    <div class="health-list">
      <div class="health-item">
        <span>API</span>
        <span class="status healthy">Healthy</span>
      </div>
      <div class="health-item">
        <span>Database</span>
        <span class="status healthy">Healthy</span>
      </div>
      <div class="health-item">
        <span>Station Sync</span>
        <span class="status healthy">Healthy</span>
      </div>
      <div class="health-item">
        <span>Payment Gateway</span>
        <span class="status healthy">Healthy</span>
      </div>
    </div>
  </div>

  <div class="panel-section">
    <div class="panel-section-title"><i data-lucide="zap"></i> Quick Actions</div>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <a class="btn-sm" href="{{ route('pos') }}" style="justify-content:flex-start;">
        <i data-lucide="circle-plus"></i> New Sale
      </a>
      @can('delivery.manage')
        <a class="btn-sm" href="{{ route('deliveries.create') }}" style="justify-content:flex-start;">
          <i data-lucide="truck"></i> Schedule Delivery
        </a>
      @endcan
      @can('report.view')
        <a class="btn-sm" href="{{ route('reports.index') }}" style="justify-content:flex-start;">
          <i data-lucide="file-text"></i> Generate Report
        </a>
      @endcan
      @can('shift.view')
        <a class="btn-sm" href="{{ route('shifts.index') }}" style="justify-content:flex-start;">
          <i data-lucide="clock"></i> Shift Handover
        </a>
      @endcan
    </div>
  </div>
</aside>