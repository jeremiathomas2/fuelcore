@extends('layouts.app')

@section('active', 'stations')
@section('page_title', 'Filling Stations')
@section('page_breadcrumb', 'FUELCORE / Stations')

@section('content')
  <div class="page-head">
    <div>
      <h1>Filling Stations</h1>
      <p>Manage stations, their status and operational details.</p>
    </div>
    <div class="page-actions">
      @can('station.manage')
        <a href="{{ route('stations.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add Station</a>
      @endcan
    </div>
  </div>

  <section class="filter-bar">
    <form action="{{ route('stations.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search name or code…" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-family:inherit;font-size:0.75rem;flex:1;min-width:220px;">
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['online', 'offline', 'maintenance', 'warning'] as $status)
          <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="filter"></i> Filter</button>
      <a href="{{ route('stations.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Code</th><th>Station</th><th>Location</th><th>Pumps</th><th>Nozzles</th><th>Tanks</th><th>Status</th><th>Last Sync</th><th class="center">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($stations as $station)
              <tr>
                <td class="mono">{{ $station->code }}</td>
                <td>
                  <span class="row-avatar">{{ strtoupper(substr($station->code, 0, 2)) }}</span>
                  <strong>{{ $station->name }}</strong>
                </td>
                <td>{{ $station->location }} <span class="muted small">· {{ $station->region }}</span></td>
                <td class="numeric">{{ $station->pumps_count }}</td>
                <td class="numeric">{{ $station->nozzles_count }}</td>
                <td class="numeric">{{ $station->tanks_count }}</td>
                <td><span class="status-pill-sm {{ $station->status === 'online' ? 'online' : ($station->status === 'offline' ? 'warning' : 'neutral') }}">{{ $station->status }}</span></td>
                <td class="muted small">{{ $station->last_sync_at?->diffForHumans() ?? '—' }}</td>
                <td class="center">
                  <div class="action-links">
                    <a href="{{ route('stations.show', $station) }}" title="View"><i data-lucide="eye"></i></a>
                    @can('station.manage')
                      <a href="{{ route('stations.edit', $station) }}" title="Edit"><i data-lucide="pencil"></i></a>
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No stations found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $stations->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection