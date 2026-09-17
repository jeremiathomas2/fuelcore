@extends('layouts.app')

@section('active', 'tanks')
@section('page_title', 'Fuel Tanks')
@section('page_breadcrumb', 'FUELCORE / Tanks')

@section('content')
  <div class="page-head">
    <div><h1>Fuel Tanks</h1><p>Monitor storage capacity, levels and status.</p></div>
    <div class="page-actions">
      @can('tank.manage')
        <a href="{{ route('tanks.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add Tank</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px;">
    @foreach ($summary as $row)
      <div class="kpi-card">
        <div class="kpi-header"><div class="kpi-icon {{ $row->status === 'normal' ? 'green' : ($row->status === 'critical' ? 'red' : ($row->status === 'low' ? 'amber' : 'blue')) }}"><i data-lucide="database"></i></div></div>
        <div class="kpi-value">{{ $row->count }}</div>
        <div class="kpi-label">{{ ucfirst($row->status) }}</div>
        <div class="kpi-secondary">{{ number_format((float) $row->volume, 0) }} / {{ number_format((float) $row->capacity, 0) }} L</div>
      </div>
    @endforeach
  </section>

  <section class="filter-bar">
    <form action="{{ route('tanks.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['normal', 'warning', 'low', 'critical'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('tanks.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Tank</th><th>Station</th><th>Fuel</th><th class="numeric">Volume</th><th class="numeric">Capacity</th><th>Level</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($tanks as $tank)
              @php($pct = $tank->levelPercent())
              <tr>
                <td class="bold">{{ $tank->tank_number }}</td>
                <td>{{ $tank->station?->name }}</td>
                <td><span class="fuel-badge petrol">{{ $tank->fuelProduct?->name }}</span></td>
                <td class="numeric bold">{{ number_format((float) $tank->current_volume, 0) }} L</td>
                <td class="numeric">{{ number_format((float) $tank->capacity, 0) }} L</td>
                <td>
                  <div class="d-flex" style="align-items:center;gap:8px;">
                    <div class="stock-bar" style="width:110px;"><div class="stock-bar-fill {{ $pct < 15 ? 'low' : ($pct < 50 ? 'mid' : 'good') }}" style="width:{{ $pct }}%;"></div></div>
                    <span class="tank-pct">{{ $pct }}%</span>
                  </div>
                </td>
                <td><span class="status-pill-sm {{ $tank->status === 'normal' ? 'online' : ($tank->status === 'critical' ? 'warning' : 'neutral') }}">{{ $tank->status }}</span></td>
                <td class="center">
                  <div class="action-links">
                    @can('tank.manage')
                      <a href="{{ route('tanks.edit', $tank) }}" title="Edit"><i data-lucide="pencil"></i></a>
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="8"><div class="empty-state">No tanks found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $tanks->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection