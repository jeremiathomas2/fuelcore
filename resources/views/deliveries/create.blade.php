@extends('layouts.app')

@section('active', 'deliveries')
@section('page_title', 'New Delivery')
@section('page_breadcrumb', 'FUELCORE / Deliveries / New')

@section('content')
  <div class="page-head">
    <div><h1>Schedule Fuel Delivery</h1></div>
    <div class="page-actions"><a href="{{ route('deliveries.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('deliveries.store') }}" class="card">
    @csrf
    <div class="card-header"><h3>Delivery Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Station *</label>
          <select class="form-control" name="station_id" required>
            <option value="">Select station</option>
            @foreach ($stations as $st)
              <option value="{{ $st->id }}" {{ old('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
          </select>
          @error('station_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Supplier *</label>
          <select class="form-control" name="supplier_id" required>
            <option value="">Select supplier</option>
            @foreach ($suppliers as $s)
              <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
          </select>
          @error('supplier_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Ordered Quantity (L) *</label>
          <input class="form-control" type="number" step="0.01" min="0" name="ordered_qty" value="{{ old('ordered_qty') }}" required>
          @error('ordered_qty')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Delivery Date *</label>
          <input class="form-control" type="datetime-local" name="delivery_date" value="{{ old('delivery_date') }}" required>
        </div>
        <div class="form-group">
          <label class="form-label">Driver Name</label>
          <input class="form-control" type="text" name="driver_name" value="{{ old('driver_name') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Vehicle Number</label>
          <input class="form-control" type="text" name="vehicle_number" value="{{ old('vehicle_number') }}" placeholder="Tank truck plate">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['scheduled', 'in_transit', 'receiving'] as $s)
              <option value="{{ $s }}" {{ old('status', 'scheduled') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
        </div>
      </div>
    </div>

    <div class="card-header"><h3>Delivery Items (optional)</h3></div>
    <div class="card-body">
      <div id="itemsWrap">
        <div class="item-row form-grid" style="grid-template-columns:1fr 1fr 1fr auto;">
          <div class="form-group">
            <select class="form-control" name="items[0][fuel_product_id]">
              <option value="">Select fuel</option>
              @foreach ($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <select class="form-control" name="items[0][tank_id]">
              <option value="">Target tank</option>
              @foreach ($products as $p)
                @foreach ($p->tanks as $t)
                  <option value="{{ $t->id }}">{{ $t->station?->code }} · {{ $t->tank_number }} ({{ $p->name }})</option>
                @endforeach
              @endforeach
            </select>
          </div>
          <div class="form-group"><input class="form-control" type="number" step="0.01" min="0" name="items[0][ordered_qty]" placeholder="Qty (L)"></div>
          <button type="button" class="btn-sm danger remove-item" style="align-self:center;">✕</button>
        </div>
      </div>
      <button type="button" class="btn-sm" id="addItem"><i data-lucide="plus"></i> Add Item</button>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Schedule Delivery</button>
      <a href="{{ route('deliveries.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection

@push('scripts')
  @php
    $productOptions = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'tanks' => $p->tanks->map(fn ($t) => ['id' => $t->id, 'label' => ($t->station?->code ?? '') . ' · ' . $t->tank_number]),
    ])->values();
  @endphp
  <script>
    (() => {
      const KEY = '__fcInit_deliveryCreate';
      if (window[KEY]) document.removeEventListener('turbo:load', window[KEY]);
      window[KEY] = () => {
      if (!document.getElementById('addItem')) return;
      let ri = 1;
      const wrap = document.getElementById('itemsWrap');
      const products = @json($productOptions);
      document.getElementById('addItem').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'item-row form-grid';
        row.style.cssText = 'grid-template-columns:1fr 1fr 1fr auto;';
        const tankOpts = []; let i = 0;
        const opts = products.map((p) => {
          const tanks = p.tanks.map((t) => `<option value="${t.id}">${t.label} (${p.name})</option>`).join('');
          return `<option value="${p.id}">${p.name}</option>`;
        });
        const productOpts = opts.join('');
        const tankAll = '<option value="">Target tank</option>' + products.map((p) => p.tanks.map((t) => `<option value="${t.id}" data-p="${p.id}">${t.label} (${p.name})</option>`).join('')).join('');
        row.innerHTML = `
          <div class="form-group"><select class="form-control" name="items[${ri}][fuel_product_id]"><option value="">Select fuel</option>${productOpts}</select></div>
          <div class="form-group"><select class="form-control" name="items[${ri}][tank_id]">${tankAll}</select></div>
          <div class="form-group"><input class="form-control" type="number" step="0.01" min="0" name="items[${ri}][ordered_qty]" placeholder="Qty (L)"></div>
          <button type="button" class="btn-sm danger remove-item" style="align-self:center;">✕</button>`;
        wrap.appendChild(row);
        ri++;
      });
      document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-item')) e.target.closest('.item-row').remove();
      });
      };
      document.addEventListener('turbo:load', window[KEY]);
    })();
  </script>
@endpush