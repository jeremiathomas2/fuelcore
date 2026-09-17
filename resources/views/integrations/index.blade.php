@extends('layouts.app')

@section('active', 'integrations')
@section('page_title', 'Integrations')
@section('page_breadcrumb', 'FUELCORE / Integrations')

@section('content')
  <div class="page-head">
    <div><h1>Integration Log</h1><p>External fuel transaction payloads processed by the API.</p></div>
  </div>

  @include('partials.flash')

  <section class="kpi-grid" style="margin-bottom:24px;">
    @php($sTone = ['processed' => 'online', 'failed' => 'error', 'pending' => 'warning', 'duplicate' => 'info'])
    @foreach (['processed', 'failed', 'pending', 'duplicate'] as $st)
      <div class="kpi-card">
        <div class="kpi-header"><div class="kpi-icon {{ $sTone[$st] }}"><i data-lucide="{{ $st === 'processed' ? 'circle-check' : ($st === 'failed' ? 'circle-x' : 'clock') }}"></i></div></div>
        <div class="kpi-value">{{ (int) $counts->get($st, 0) }}</div>
        <div class="kpi-label">{{ ucfirst($st) }}</div>
      </div>
    @endforeach
  </section>

  <section class="filter-bar">
    <form action="{{ route('integrations.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="UUID, station code, error…" style="flex:1;min-width:220px;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['processed', 'failed', 'pending', 'duplicate'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="filter"></i> Filter</button>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Received</th><th>UUID</th><th>Station</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($transactions as $it)
              <tr>
                <td class="muted small">{{ $it->created_at->format('d M Y H:i:s') }}</td>
                <td class="mono small">{{ substr($it->transaction_uuid, 0, 12) }}…</td>
                <td>{{ $it->station_code }}</td>
                <td>
                  @php($st = ['processed' => 'online', 'failed' => 'error', 'pending' => 'warning', 'duplicate' => 'info'][$it->status] ?? 'neutral')
                  <span class="status-pill-sm {{ $st }}">{{ $it->status }}</span>
                  @if ($it->error_message)
                    <div class="muted small" style="margin-top:2px;" title="{{ $it->error_message }}">{{ Str::limit($it->error_message, 60) }}</div>
                  @endif
                </td>
                <td class="center">
                  @if ($it->status === 'failed')
                    <form method="POST" action="{{ route('integrations.retry', $it) }}" style="display:inline;">
                      @csrf
                      <button class="btn-sm" type="submit" title="Retry"><i data-lucide="rotate-ccw"></i> Retry</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5"><div class="empty-state">No integration transactions.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $transactions->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection