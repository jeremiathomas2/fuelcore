@extends('layouts.app')

@section('active', 'pumps')
@section('page_title', 'Fuel Pumps')
@section('page_breadcrumb', 'FUELCORE / Pumps')

@section('content')
  <div class="page-head">
    <div><h1>Fuel Pumps</h1><p>Track pump equipment, status and telemetry.</p></div>
    <div class="page-actions">
      @can('pump.manage')
        <a href="{{ route('pumps.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add Pump</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('pumps.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search pump #, serial…" style="flex:1;min-width:180px;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-family:inherit;font-size:0.75rem;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['online', 'offline', 'maintenance', 'error'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="filter"></i> Filter</button>
      <a href="{{ route('pumps.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Pump</th><th>Station</th><th>Make / Model</th><th>Serial</th><th>Nozzles</th><th>Status</th><th>Installed</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($pumps as $pump)
              <tr>
                <td class="bold">Pump {{ $pump->pump_number }}</td>
                <td>{{ $pump->station?->name }}</td>
                <td class="muted small">{{ $pump->manufacturer }} {{ $pump->model }}</td>
                <td class="mono small">{{ $pump->serial_number ?? '—' }}</td>
                <td class="numeric">{{ $pump->nozzles_count }}</td>
                <td><span class="status-pill-sm {{ $pump->status === 'online' ? 'online' : ($pump->status === 'maintenance' ? 'neutral' : 'warning') }}">{{ $pump->status }}</span></td>
                <td class="muted small">{{ $pump->installation_date?->format('d M Y') ?? '—' }}</td>
                <td class="center">
                  <div class="action-links">
                    @can('pump.manage')
                      <a href="{{ route('pumps.edit', $pump) }}" title="Edit"><i data-lucide="pencil"></i></a>
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="8"><div class="empty-state">No pumps found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $pumps->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection
