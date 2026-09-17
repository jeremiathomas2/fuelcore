@extends('layouts.app')

@section('active', 'pos')
@section('page_title', 'Point of Sale')
@section('page_breadcrumb', 'FUELCORE / Sales / POS')

@section('content')
  <div class="page-head">
    <div><h1>Point of Sale</h1><p>Complete a fuel sale quickly. All figures are in {{ currency() }}.</p></div>
    <div class="page-actions">
      @if ($openShift && $station)
        <span class="status-pill-sm online"><i data-lucide="clock" style="width:11px;height:11px;"></i> Shift open · {{ $openShift->opened_at?->format('H:i') ?? '—' }}</span>
      @else
        <a href="{{ route('shifts.index') }}" class="btn btn-sm"><i data-lucide="clock"></i> Open Shift Required</a>
      @endif
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('pos') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-family:inherit;font-size:0.75rem;">
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ $station?->id === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn-sm primary"><i data-lucide="check"></i> Select</button>
    </form>
  </section>

  @if (! $station)
    <div class="card"><div class="card-body"><div class="empty-state"><i data-lucide="building-2"></i> Select a station to begin selling.</div></div></div>
  @else
    <form method="POST" action="{{ route('pos.complete') }}" id="posForm">
      @csrf
      <input type="hidden" name="station_id" value="{{ $station->id }}">
      <input type="hidden" name="shift_id" value="{{ $openShift?->id }}">
      <input type="hidden" name="fuel_type" id="fuelType" value="volume">
      <input type="hidden" name="fuel_product_id" id="fuelProductId" value="">

      <section class="two-col-even" style="align-items:start;">
        <!-- LEFT: nozzle selection -->
        <div class="card">
          <div class="card-header"><h3><i data-lucide="fuel"></i> Select Nozzle</h3></div>
          <div class="card-body">
            <div class="nozzle-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));">
              @forelse ($nozzles as $nozzle)
                <label class="nozzle-card pos-nozzle" style="cursor:pointer;">
                  <input type="radio" name="nozzle_id" value="{{ $nozzle->id }}" class="pos-radio" data-product="{{ $nozzle->fuel_product_id }}" data-price="{{ $nozzle->fuelProduct?->price }}">
                  <div class="nozzle-header">
                    <span class="nozzle-id">{{ $station->code }} · Pump {{ $nozzle->pump?->pump_number }} · {{ $nozzle->nozzle_number }}</span>
                    <span class="nozzle-status online"><span class="status-dot"></span>{{ $nozzle->status }}</span>
                  </div>
                  <div class="nozzle-values">
                    <div class="nozzle-value-item"><span class="val">{{ number_format((float) $nozzle->fuelProduct?->price, 0) }}</span><span class="label">{{ currency() }}/L</span></div>
                  </div>
                  <div class="nozzle-status-row"><span class="fuel-badge petrol">{{ $nozzle->fuelProduct?->name }}</span></div>
                </label>
              @empty
                <div class="empty-state" style="grid-column:1/-1;">No nozzles available at this station.</div>
              @endforelse
            </div>
            <div class="form-group" style="margin-top:14px;">
              <label class="form-label">Or select fuel directly</label>
              <select class="form-control" id="productDirect">
                <option value="">—</option>
                @foreach ($products as $p)
                  <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->name }} — {{ currency() }} {{ number_format((float) $p->price, 0) }}/L</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        <!-- RIGHT: transaction -->
        <div class="card">
          <div class="card-header"><h3><i data-lucide="receipt"></i> New Sale</h3></div>
          <div class="card-body">
            <div class="form-grid" style="grid-template-columns:1fr 1fr;">
              <div class="form-group">
                <label class="form-label">Fuel Type</label>
                <div class="segment" id="fuelTypeSegment">
                  <button type="button" class="segment-btn active" data-type="volume"><i data-lucide="droplets"></i> By Litres</button>
                  <button type="button" class="segment-btn" data-type="amount"><i data-lucide="banknote"></i> By Amount</button>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" id="inputLabel">Litres</label>
                <input class="form-control" type="number" step="0.001" min="0" name="litres" id="litresInput" placeholder="0.00">
              </div>
            </div>

            <div class="form-grid" style="grid-template-columns:1fr 1fr;">
              <div class="form-group">
                <label class="form-label">Price ({{ currency() }}/L)</label>
                <input class="form-control" type="number" step="0.01" name="price" id="priceInput" placeholder="0.00" readonly>
              </div>
              <div class="form-group">
                <label class="form-label">Discount</label>
                <input class="form-control" type="number" step="0.01" min="0" name="discount" id="discountInput" value="0" placeholder="0.00">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Payment Method *</label>
              <select class="form-control" name="payment_method" id="paymentMethod" required>
                @foreach (['cash' => 'Cash', 'mobile_money' => 'Mobile Money (M-Pesa/Tigo Pesa)', 'card' => 'Card', 'fleet_account' => 'Fleet Account', 'credit' => 'Credit'] as $val => $label)
                  <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div id="fleetFields" style="display:none;">
              <div class="form-grid" style="grid-template-columns:1fr 1fr;">
                <div class="form-group">
                  <label class="form-label">Fleet Account</label>
                  <select class="form-control" name="fleet_account_id">
                    <option value="">Select account</option>
                    @foreach ($fleetAccounts as $fa)
                      <option value="{{ $fa->id }}">{{ $fa->company_name }} ({{ $fa->account_number }})</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Vehicle / Plate</label>
                  <input class="form-control" type="text" name="vehicle_plate" placeholder="e.g. T 123 ABC">
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Payment Reference</label>
              <input class="form-control" type="text" name="payment_reference" id="paymentReference" placeholder="Trx ID / M-Pesa ref / card last 4…">
            </div>

            <div class="form-group">
              <label class="form-label">Customer (optional)</label>
              <select class="form-control" name="customer_id">
                <option value="">Walk-in customer</option>
                @foreach ($customers as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="sale-total">
              <div class="total-line"><span>Amount</span><span id="amountDisplay">{{ currency() }} 0</span></div>
              <div class="total-line muted small"><span>Tax</span><span id="taxDisplay">{{ currency() }} 0</span></div>
              <div class="total-line"><span>Discount</span><span id="discountDisplay">0</span></div>
              <div class="total-line grand"><span>Total Due</span><span id="totalDisplay">{{ currency() }} 0</span></div>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary btn-block"><i data-lucide="check"></i> Complete Sale</button>
          </div>
        </div>
      </section>
    </form>

    @error('error')
      <div class="alert-box alert-error" style="margin-top:16px;"><i data-lucide="alert-triangle"></i><div>{{ $message }}</div></div>
    @enderror
  @endif
@endsection

@push('scripts')
  <script>
    (() => {
      const KEY = '__fcInit_pos';
      if (window[KEY]) document.removeEventListener('turbo:load', window[KEY]);
      window[KEY] = () => {
      if (!document.getElementById('litresInput')) return;
      const $ = (s) => document.querySelector(s);
      let fuelType = 'volume';
      let price = 0;

      const setInputVisibility = () => {
        const isAmount = fuelType === 'amount';
        $('#inputLabel').textContent = isAmount ? 'Amount ({{ currency() }})' : 'Litres';
        if (isAmount) {
          $('#litresInput').name = 'amount';
          $('#litresInput').step = '0.01';
          $('#litresInput').placeholder = '0.00 {{ currency() }}';
        } else {
          $('#litresInput').name = 'litres';
          $('#litresInput').step = '0.001';
          $('#litresInput').placeholder = '0.00';
        }
      };

      const recalc = () => {
        const qty = parseFloat($('#litresInput').value) || 0;
        let base = qty * (price || 0);
        if (fuelType === 'amount') base = qty;
        const discount = parseFloat($('#discountInput').value) || 0;
        const tax = base * 0.18; // assume 18% VAT view estimate
        const total = Math.max(0, base - discount);
        $('#amountDisplay').textContent = '{{ currency() }} ' + base.toLocaleString('en', { maximumFractionDigits: 0 });
        $('#taxDisplay').textContent = '{{ currency() }} ' + tax.toLocaleString('en', { maximumFractionDigits: 0 });
        $('#discountDisplay').textContent = discount.toLocaleString('en', { maximumFractionDigits: 2 });
        $('#totalDisplay').textContent = '{{ currency() }} ' + total.toLocaleString('en', { maximumFractionDigits: 0 });
      };

      document.querySelectorAll('.segment-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          document.querySelectorAll('.segment-btn').forEach((b) => b.classList.remove('active'));
          btn.classList.add('active');
          fuelType = btn.dataset.type;
          $('#fuelType').value = fuelType;
          setInputVisibility();
          recalc();
        });
      });

      document.querySelectorAll('.pos-radio').forEach((r) => {
        r.addEventListener('change', () => {
          price = parseFloat(r.dataset.price) || 0;
          $('#priceInput').value = price;
          $('#fuelProductId').value = r.dataset.product;
          const direct = $('#productDirect');
          if (direct) direct.value = '';
          recalc();
        });
      });

      $('#productDirect').addEventListener('change', (e) => {
        const opt = e.target.selectedOptions[0];
        if (opt && opt.value) {
          price = parseFloat(opt.dataset.price) || 0;
          $('#priceInput').value = price;
          $('#fuelProductId').value = opt.value;
          const checked = document.querySelector('.pos-radio:checked');
          if (checked) checked.checked = false;
        } else {
          $('#fuelProductId').value = '';
        }
        recalc();
      });

      $('#litresInput').addEventListener('input', recalc);
      $('#discountInput').addEventListener('input', recalc);

      $('#paymentMethod').addEventListener('change', (e) => {
        const isFleet = e.target.value === 'fleet_account';
        $('#fleetFields').style.display = isFleet ? 'block' : 'none';
        if (!isFleet) {
          $('#fleetFields').querySelector('select').value = '';
          $('#fleetFields').querySelector('input').value = '';
        }
      });

      setInputVisibility();
      };
      document.addEventListener('turbo:load', window[KEY]);
    })();
  </script>
@endpush