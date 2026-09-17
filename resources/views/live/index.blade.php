@extends('layouts.app')

@section('active', 'live')
@section('page_title', 'Live Operations')
@section('page_breadcrumb', 'FUELCORE / Monitoring')

@section('content')
  <div class="page-head">
    <div>
      <h1>Live Operations Monitor</h1>
      <p>Real-time pump, nozzle and device telemetry across your stations.</p>
    </div>
    <div class="page-actions">
      <span class="live-badge" id="liveBadge"><span class="dot"></span> Streaming</span>
      <button class="btn btn-sm" id="refreshLive"><i data-lucide="refresh-cw"></i> Refresh</button>
    </div>
  </div>

  <section class="filter-bar">
    <form action="{{ route('live') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-family:inherit;font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ $selectedStation?->id === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn-sm primary"><i data-lucide="funnel"></i> Filter</button>
    </form>
  </section>

  <section class="card">
    <div class="card-header">
      <h3><i data-lucide="radio"></i> Nozzle Telemetry</h3>
      <div class="section-actions muted small" id="liveClock"></div>
    </div>
    <div class="card-body">
      <div class="nozzle-grid" id="nozzleGrid">
        <div class="empty-state" style="grid-column:1/-1;"><i data-lucide="loader"></i>Connecting…</div>
      </div>
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    const LIVE_URL = '{{ route('live.data', ['station' => $selectedStation?->id ?? '']) }}';
    const TSh = '{{ currency() }}';
    const statusBadge = (s) => {
      const cls = s === 'dispensing' ? 'dispensing' : ['idle','completed'].includes(s) ? 'online' : 'warning';
      return `<span class="nozzle-status ${cls}"><span class="status-dot"></span>${s}</span>`;
    };
    async function loadLive() {
      try {
        const res = await fetch(LIVE_URL, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        document.getElementById('liveClock').textContent = 'Stream update: ' + data.now;
        const grid = document.getElementById('nozzleGrid');
        if (!data.nozzles.length) {
          grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;">No nozzle telemetry available.</div>';
        } else {
          grid.innerHTML = data.nozzles.map((n) => `
            <div class="nozzle-card">
              <div class="nozzle-header">
                <span class="nozzle-id">${n.station} · Pump ${n.pump} · Nozzle ${n.number}</span>
                ${statusBadge(n.status)}
              </div>
              <div class="nozzle-values">
                <div class="nozzle-value-item"><span class="val">${Number(n.price||0).toLocaleString()}</span><span class="label">${TSh}/L</span></div>
                <div class="nozzle-value-item"><span class="val">${n.litres.toLocaleString()}</span><span class="label">Total L</span></div>
              </div>
              <div class="nozzle-status-row">
                <span class="fuel-badge petrol">${n.fuel}</span>
              </div>
            </div>`).join('');
        }
      } catch (e) {
        /* silent */
      }
    }
    document.addEventListener('DOMContentLoaded', () => {
      loadLive();
      setInterval(loadLive, 5000);
      document.getElementById('refreshLive').addEventListener('click', loadLive);
    });
  </script>
@endpush