@extends('layouts.app')

@section('active', 'inventory')
@section('page_title', 'Inventory')
@section('page_breadcrumb', 'FUELCORE / Inventory')

@section('content')
  <div class="page-head">
    <div>
      <h1>Inventory</h1>
      @if ($summary)
        <p class="muted">Total stocked: {{ number_format((float) $summary['litres'], 0) }} L across stations.</p>
      @endif
    </div>
    <div class="page-actions">
      <a href="{{ route('inventory.movements') }}" class="btn"><i data-lucide="history"></i> Movements</a>
      @can('inventory.manage')
        <button type="button" class="btn btn-primary" onclick="window.FcModal?.open ? window.FcModal.open('adjustModal') : document.getElementById('adjustModal').classList.add('open')"><i data-lucide="sliders-horizontal"></i> Adjust Stock</button>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  @if ($lowStock->isNotEmpty())
    <div class="alert-box alert-error" style="margin-bottom:24px;">
      <i data-lucide="triangle-alert"></i>
      <div>
        <strong>Low stock warnings:</strong>
        <ul style="margin:6px 0 0 16px;padding:0;">
          @foreach ($lowStock as $ls)
            <li>{{ $ls['station']->name }} — {{ $ls['product']->name }} at {{ number_format((float) $ls['stock'], 0) }} L</li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Station</th><th>Fuel</th><th class="numeric">Stock (L)</th><th class="numeric">Capacity (L)</th><th>Level</th></tr></thead>
          <tbody>
            @forelse ($stock as $row)
              @foreach ($row['products'] as $item)
                @php($pct = $item['capacity'] > 0 ? round($item['stock'] / $item['capacity'] * 100, 1) : 0)
                <tr>
                  <td class="bold">{{ $row['station']->name }}</td>
                  <td><span class="fuel-badge petrol">{{ $item['product']->name }}</span></td>
                  <td class="numeric bold">{{ number_format((float) $item['stock'], 0) }} L</td>
                  <td class="numeric">{{ number_format((float) $item['capacity'], 0) }} L</td>
                  <td>
                    <div class="d-flex" style="align-items:center;gap:8px;">
                      <div class="stock-bar" style="width:110px;"><div class="stock-bar-fill {{ $pct < 15 ? 'low' : ($pct < 50 ? 'mid' : 'good') }}" style="width:{{ $pct }}%;"></div></div>
                      <span class="tank-pct">{{ $pct }}%</span>
                    </div>
                  </td>
                </tr>
              @endforeach
            @empty
              <tr><td colspan="5"><div class="empty-state">No inventory available.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @can('inventory.manage')
    <form class="modal-overlay" id="adjustModal" method="POST" action="{{ route('inventory.adjust') }}">
      @csrf
      <div class="modal">
        <div class="modal-header"><h3>Adjust Stock</h3><button type="button" class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('open')"><i data-lucide="x"></i></button></div>
        <div class="modal-body">
          <div class="form-grid" style="grid-template-columns:1fr 1fr;">
            <div class="form-group">
              <label class="form-label">Station *</label>
              <select class="form-control" name="station_id" required>
                <option value="">Select</option>
                @foreach ($stations as $st)
                  <option value="{{ $st->id }}">{{ $st->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Fuel *</label>
              <select class="form-control" name="fuel_product_id" required>
                <option value="">Select</option>
                @foreach ($products as $p)
                  <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Quantity (+/- L) *</label>
              <input class="form-control" type="number" step="0.01" name="quantity" placeholder="e.g. -50 or 100" required>
            </div>
            <div class="form-group">
              <label class="form-label">Note *</label>
              <input class="form-control" type="text" name="note" placeholder="Reason for adjustment" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm" onclick="this.closest('.modal-overlay').classList.remove('open')">Cancel</button>
          <button type="submit" class="btn btn-sm primary"><i data-lucide="check"></i> Apply Adjustment</button>
        </div>
      </div>
    </form>
  @endcan
@endsection