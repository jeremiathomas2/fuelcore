@extends('layouts.app')

@section('active', 'nozzles')
@section('page_title', 'Nozzles')
@section('page_breadcrumb', 'FUELCORE / Nozzles')

@section('content')
  <div class="page-head">
    <div><h1>Nozzles</h1><p>Manage nozzle assignments, meter readings and fuel products.</p></div>
    <div class="page-actions">
      @can('nozzle.manage')
        <a href="{{ route('nozzles.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add Nozzle</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('nozzles.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['idle', 'dispensing', 'completed', 'offline', 'error', 'maintenance'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('nozzles.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Nozzle</th><th>Pump</th><th>Station</th><th>Fuel</th><th class="numeric">Meter Start</th><th class="numeric">Meter Current</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($nozzles as $nozzle)
              <tr>
                <td class="bold">Nozzle {{ $nozzle->nozzle_number }}</td>
                <td>Pump {{ $nozzle->pump?->pump_number }}</td>
                <td class="muted small">{{ $nozzle->pump?->station?->name }}</td>
                <td><span class="fuel-badge petrol">{{ $nozzle->fuelProduct?->name }}</span></td>
                <td class="numeric">{{ number_format((float) $nozzle->meter_start, 2) }}</td>
                <td class="numeric bold">{{ number_format((float) $nozzle->meter_current, 2) }}</td>
                <td><span class="status-pill-sm {{ in_array($nozzle->status, ['idle', 'completed']) ? 'online' : 'warning' }}">{{ $nozzle->status }}</span></td>
                <td class="center">
                  <div class="action-links">
                    @can('nozzle.manage')
                      <a href="{{ route('nozzles.edit', $nozzle) }}" title="Edit"><i data-lucide="pencil"></i></a>
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="8"><div class="empty-state">No nozzles found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $nozzles->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection
