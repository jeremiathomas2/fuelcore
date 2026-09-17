@extends('layouts.app')

@section('active', 'deliveries')
@section('page_title', 'Deliveries')
@section('page_breadcrumb', 'FUELCORE / Deliveries')

@section('content')
  <div class="page-head">
    <div><h1>Fuel Deliveries</h1><p>Schedule, receive and reconcile fuel deliveries.</p></div>
    <div class="page-actions">
      @can('delivery.manage')
        <a href="{{ route('deliveries.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> New Delivery</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('deliveries.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['scheduled', 'in_transit', 'receiving', 'completed', 'reconciled', 'disputed', 'cancelled'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('deliveries.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Delivery No</th><th>Station</th><th>Supplier</th><th class="numeric">Ordered</th><th class="numeric">Delivered</th><th class="numeric">Variance</th><th>Date</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($deliveries as $d)
              <tr onclick="window.location='{{ route('deliveries.show', $d) }}'">
                <td class="mono">{{ $d->delivery_number }}</td>
                <td>{{ $d->station?->name }}</td>
                <td class="muted small">{{ $d->supplier?->name }}</td>
                <td class="numeric">{{ number_format((float) $d->ordered_qty, 0) }} L</td>
                <td class="numeric">{{ $d->delivered_qty ? number_format((float) $d->delivered_qty, 0).' L' : '—' }}</td>
                <td class="numeric {{ (float) $d->variance < 0 ? 'text-danger' : '' }}">{{ $d->variance !== null ? number_format((float) $d->variance, 1).' L' : '—' }}</td>
                <td class="muted small">{{ $d->delivery_date->format('d M Y H:i') }}</td>
                <td><span class="status-pill-sm {{ in_array($d->status, ['completed', 'reconciled']) ? 'online' : ($d->status === 'cancelled' ? 'warning' : 'neutral') }}">{{ $d->status }}</span></td>
                <td class="center">
                  <div class="action-links">
                    <a href="{{ route('deliveries.show', $d) }}" title="View"><i data-lucide="eye"></i></a>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No deliveries found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $deliveries->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection