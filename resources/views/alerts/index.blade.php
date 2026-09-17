@extends('layouts.app')

@section('active', 'alerts')
@section('page_title', 'Alerts')
@section('page_breadcrumb', 'FUELCORE / Alerts')

@section('content')
  <div class="page-head">
    <div><h1>System Alerts</h1><p>Tank levels, delivery confirmation and operational warnings.</p></div>
    <div class="page-actions">
      <a href="{{ route('notifications.index') }}" class="btn"><i data-lucide="bell"></i> Notifications</a>
    </div>
  </div>

  @include('partials.flash')

  <section class="kpi-grid" style="margin-bottom:24px;">
    @php($tone = ['critical' => 'red', 'warning' => 'amber', 'info' => 'blue'])
    @php($icon = ['critical' => 'octagon-alert', 'warning' => 'triangle-alert', 'info' => 'info'])
    @foreach (['critical', 'warning', 'info'] as $sev)
      <div class="kpi-card">
        <div class="kpi-header"><div class="kpi-icon {{ $tone[$sev] }}"><i data-lucide="{{ $icon[$sev] }}"></i></div></div>
        <div class="kpi-value">{{ (int) $counts->get($sev, 0) }}</div>
        <div class="kpi-label">{{ ucfirst($sev) }} open alerts</div>
      </div>
    @endforeach
  </section>

  <section class="filter-bar">
    <form action="{{ route('alerts.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="severity" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Severities</option>
        @foreach (['critical', 'warning', 'info'] as $s)
          <option value="{{ $s }}" {{ request('severity') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <label class="filter-chip" style="display:flex;gap:6px;align-items:center;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <input type="checkbox" name="resolved" value="1" {{ request('resolved') ? 'checked' : '' }}> Show resolved
      </label>
      <button class="btn-sm primary" type="submit"><i data-lucide="filter"></i> Filter</button>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Severity</th><th>Alert</th><th>Station</th><th>Status</th><th>Triggered</th><th>Resolved</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($alerts as $a)
              @php($sevTone = ['critical' => 'error', 'warning' => 'warning', 'info' => 'info'][$a->severity] ?? 'neutral')
              <tr>
                <td><span class="status-pill-sm {{ $sevTone }}">{{ $a->severity }}</span></td>
                <td class="bold">{{ $a->title }}</td>
                <td class="muted small">{{ $a->station?->name ?? 'System-wide' }}</td>
                <td>
                  @if ($a->resolved_at)
                    <span class="status-pill-sm online">Resolved</span>
                  @else
                    <span class="status-pill-sm warning">Open</span>
                  @endif
                </td>
                <td class="muted small">{{ $a->created_at->format('d M Y H:i') }}</td>
                <td class="muted small">{{ $a->resolved_at?->format('d M Y H:i') ?? '—' }}</td>
                <td class="center">
                  @if (!$a->resolved_at)
                    <form method="POST" action="{{ route('alerts.resolve', $a) }}" style="display:inline;">
                      @csrf
                      <button class="btn-sm" type="submit" title="Mark resolved"><i data-lucide="check"></i> Resolve</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7"><div class="empty-state"><i data-lucide="circle-check"></i> No alerts. All clear.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $alerts->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection