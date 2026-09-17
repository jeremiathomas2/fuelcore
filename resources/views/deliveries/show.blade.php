@extends('layouts.app')

@section('active', 'deliveries')
@section('page_title', $delivery->delivery_number)
@section('page_breadcrumb', 'FUELCORE / Deliveries / '.$delivery->delivery_number)

@section('content')
  <div class="page-head">
    <div><h1>{{ $delivery->delivery_number }}</h1><p>{{ $delivery->station?->name }} · {{ $delivery->delivery_date->format('d M Y H:i') }}</p></div>
    <div class="page-actions">
      @if (in_array($delivery->status, ['scheduled', 'in_transit', 'receiving']))
        @can('delivery.manage')
          <form method="POST" action="{{ route('deliveries.cancel', $delivery) }}" class="d-inline" data-turbo="false"
                data-confirm="Cancel delivery {{ $delivery->delivery_number }}? This cannot be undone."
                data-confirm-title="Cancel delivery" data-confirm-ok="Cancel delivery" data-confirm-icon="ban">
            @csrf
            <button type="submit" class="btn btn-danger"><i data-lucide="ban"></i> Cancel</button>
          </form>
        @endcan
      @endif
      <a href="{{ route('deliveries.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  @include('partials.flash')

  <section class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon blue"><i data-lucide="package-open"></i></div></div>
      <div class="kpi-value">{{ number_format((float) $delivery->ordered_qty, 0) }} L</div>
      <div class="kpi-label">Ordered</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon green"><i data-lucide="circle-check"></i></div></div>
      <div class="kpi-value">{{ $delivery->delivered_qty !== null ? number_format((float) $delivery->delivered_qty, 0).' L' : '—' }}</div>
      <div class="kpi-label">Delivered</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon amber"><i data-lucide="scale"></i></div></div>
      <div class="kpi-value">{{ $delivery->variance !== null ? number_format((float) $delivery->variance, 1).' L' : '—' }}</div>
      <div class="kpi-label">Variance</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon {{ in_array($delivery->status, ['completed', 'reconciled']) ? 'green' : 'blue' }}"><i data-lucide="flag"></i></div></div>
      <div class="kpi-value">{{ ucfirst(str_replace('_', ' ', $delivery->status)) }}</div>
      <div class="kpi-label">Status</div>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3><i data-lucide="info"></i> Delivery Information</h3></div>
    <div class="card-body">
      <div class="detail-grid">
        <div class="detail-item"><div class="dt">Supplier</div><div class="dd">{{ $delivery->supplier?->name ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Station</div><div class="dd">{{ $delivery->station?->name ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Driver</div><div class="dd">{{ $delivery->driver_name ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Vehicle</div><div class="dd">{{ $delivery->vehicle_number ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Received By</div><div class="dd">{{ $delivery->receiving_employee ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Completed</div><div class="dd">{{ $delivery->completed_at?->format('d M Y H:i') ?? '—' }}</div></div>
        <div class="detail-item"><div class="dt">Notes</div><div class="dd">{{ $delivery->notes ?? '—' }}</div></div>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-header"><h3><i data-lucide="list"></i> Items</h3></div>
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Fuel</th><th>Target Tank</th><th class="numeric">Ordered</th><th class="numeric">Delivered</th><th class="numeric">Unit Price</th><th class="numeric">Cost</th></tr></thead>
          <tbody>
            @forelse ($delivery->items as $item)
              <tr>
                <td>{{ $item->product?->name }}</td>
                <td class="muted small">{{ $item->tank?->tank_number ?? '—' }}</td>
                <td class="numeric">{{ number_format((float) $item->ordered_qty, 2) }} L</td>
                <td class="numeric bold">{{ number_format((float) $item->delivered_qty, 2) }} L</td>
                <td class="numeric">{{ $item->unit_price ? currency().' '.number_format((float) $item->unit_price, 0) : '—' }}</td>
                <td class="numeric">{{ $item->total_cost ? currency().' '.number_format((float) $item->total_cost, 0) : '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="6"><div class="empty-state">No itemised quantities.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>

  @if (in_array($delivery->status, ['scheduled', 'in_transit', 'receiving']))
    @can('delivery.manage')
      <form method="POST" action="{{ route('deliveries.complete', $delivery) }}" class="card">
        @csrf
        <div class="card-header"><h3><i data-lucide="package-check"></i> Receive Delivery</h3></div>
        <div class="card-body">
          <p class="muted small" style="margin-bottom:14px;">Enter the actual quantity received per item and select the target tank. Stock will be added to the tank automatically.</p>
          @forelse ($delivery->items as $index => $item)
            @php($availableTanks = $tanks->where('fuel_product_id', $item->fuel_product_id))
            <div class="form-grid" style="grid-template-columns:1.5fr 2fr 1fr auto;align-items:center;margin-bottom:10px;">
              <label class="form-label" style="margin:0;">{{ $item->product?->name }}</label>
              <select class="form-control" name="delivered[{{ $item->id }}][tank_id]">
                <option value="">Select tank</option>
                @foreach ($availableTanks as $t)
                  <option value="{{ $t->id }}" {{ $item->tank_id == $t->id ? 'selected' : '' }}>{{ $t->tank_number }} ({{ number_format((float) $t->current_volume, 0) }}/{{ number_format((float) $t->capacity, 0) }} L)</option>
                @endforeach
              </select>
              <input class="form-control" type="number" step="0.01" min="0" name="delivered[{{ $item->id }}][delivered_qty]" value="{{ old('delivered.'.$item->id.'.delivered_qty', $item->ordered_qty) }}" placeholder="Received (L)">
              <span class="muted small" style="white-space:nowrap;">of {{ number_format((float) $item->ordered_qty, 0) }} L</span>
            </div>
          @empty
            <p class="muted small" style="margin-bottom:14px;">No itemised rows on this delivery. Record the fuel and tank received below.</p>
            <div class="form-grid" style="grid-template-columns:1.5fr 2fr 1fr;align-items:center;margin-bottom:10px;">
              <select class="form-control" name="fuel_product_id" required>
                <option value="">Select fuel</option>
                @foreach ($products as $p)
                  <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
              </select>
              <select class="form-control" name="tank_id" required>
                <option value="">Select tank</option>
                @foreach ($tanks as $t)
                  <option value="{{ $t->id }}">{{ $t->tank_number }} — {{ $t->fuelProduct?->name }} ({{ number_format((float) $t->current_volume, 0) }}/{{ number_format((float) $t->capacity, 0) }} L)</option>
                @endforeach
              </select>
              <input class="form-control" type="number" step="0.01" min="0" name="delivered_qty" value="{{ old('delivered_qty', $delivery->ordered_qty) }}" placeholder="Received (L)" required>
            </div>
          @endforelse
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary"><i data-lucide="package-check"></i> Confirm Receipt</button>
        </div>
      </form>
    @endcan
  @endif
@endsection
