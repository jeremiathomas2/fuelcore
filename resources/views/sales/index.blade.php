@extends('layouts.app')

@section('active', 'pos')
@section('page_title', 'Fuel Sales')
@section('page_breadcrumb', 'FUELCORE / Sales')

@section('content')
  <div class="page-head">
    <div>
      <h1>Fuel Sales</h1>
      <p class="muted">
        @if ($totals)
          {{ number_format((float) $totals->count, 0) }} transactions · {{ number_format((float) $totals->litres, 0) }} L · {{ currency() }} {{ number_format((float) $totals->revenue, 0) }}
        @endif
      </p>
    </div>
    <div class="page-actions">
      @can('pos.use')
        <a href="{{ route('pos') }}" class="btn btn-primary"><i data-lucide="plus"></i> New Sale</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('sales.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search receipt…" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="product_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Fuels</option>
        @foreach ($products as $p)
          <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
        @endforeach
      </select>
      <select name="payment_method" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Payments</option>
        @foreach (['cash', 'mobile_money', 'card', 'fleet_account', 'credit'] as $m)
          <option value="{{ $m }}" {{ request('payment_method') === $m ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
        @endforeach
      </select>
      <input type="date" name="from" value="{{ request('from') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <input type="date" name="to" value="{{ request('to') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <button class="btn-sm primary" type="submit"><i data-lucide="filter"></i> Filter</button>
      <a href="{{ route('sales.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr><th>Receipt</th><th>Station</th><th>Fuel</th><th class="numeric">Litres</th><th class="numeric">Amount</th><th>Payment</th><th>Customer</th><th>Attendant</th><th>Time</th><th class="center">Actions</th></tr>
          </thead>
          <tbody>
            @forelse ($transactions as $t)
              <tr>
                <td class="mono"><a href="#" class="txn-link" data-txn="{{ route('sales.show', $t) }}">{{ $t->transaction_number }}</a></td>
                <td>{{ $t->station?->name }}</td>
                <td><span class="fuel-badge petrol">{{ $t->fuelProduct?->name }}</span></td>
                <td class="numeric bold">{{ number_format((float) $t->litres, 2) }} L</td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float) $t->net_amount, 0) }}</td>
                <td>
                  @if ($t->payment)
                    <span class="badge badge-{{ $t->payment->method === 'cash' ? 'success' : 'info' }}">{{ ucfirst(str_replace('_', ' ', $t->payment->method)) }}</span>
                  @else
                    <span class="muted small">—</span>
                  @endif
                </td>
                <td class="muted small">{{ $t->customer?->name }}</td>
                <td class="muted small">{{ $t->attendant?->name }}</td>
                <td class="muted small">{{ $t->transacted_at->format('d M Y H:i') }}</td>
                <td class="center">
                  <div class="action-links">
                    <a href="#" class="txn-link" data-txn="{{ route('sales.show', $t) }}" title="View"><i data-lucide="eye"></i></a>
                    @can('sale.void')
                      @if ($t->status === 'completed')
                        <a href="#" class="void-link" data-void="{{ route('sales.void', $t) }}" title="Void"><i data-lucide="ban"></i></a>
                      @endif
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="10"><div class="empty-state">No sales found. Start a sale from <a class="link" href="{{ route('pos') }}">POS</a>.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $transactions->withQueryString()->links('partials.pagination') }}
    </div>
  </div>

  <!-- Transaction detail modal -->
  <div class="modal-overlay" id="txnModal">
    <div class="modal">
      <div class="modal-header"><h3>Transaction Details</h3><button type="button" class="modal-close" aria-label="Close"><i data-lucide="x"></i></button></div>
      <div class="modal-body" id="txnModalBody"><div class="empty-state"><i data-lucide="loader"></i>Loading…</div></div>
      <div class="modal-footer"><button type="button" class="btn btn-sm" onclick="window.FcModal?.closeAll ? window.FcModal.closeAll() : document.getElementById('txnModal').classList.remove('open')">Close</button></div>
    </div>
  </div>

  <!-- Void -->
  <div class="modal-overlay" id="voidModal">
    <form class="modal" method="POST" id="voidForm">
      @csrf
      <div class="modal-header"><h3>Void Transaction</h3><button type="button" class="modal-close" aria-label="Close"><i data-lucide="x"></i></button></div>
      <div class="modal-body">
        <p class="muted small">Voiding reverses the sale and restores inventory. This action is recorded in the audit log.</p>
        <div class="form-group"><label class="form-label">Reason *</label><textarea class="form-control" name="reason" rows="3" required placeholder="e.g. Wrong fuel type dispensed"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm" onclick="document.getElementById('voidModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-sm danger"><i data-lucide="ban"></i> Confirm Void</button>
      </div>
    </form>
  </div>
@endsection

@push('scripts')
  <script>
    (() => {
      const KEY = '__fcInit_sales';
      if (window[KEY]) document.removeEventListener('turbo:load', window[KEY]);
      window[KEY] = () => {
      if (!document.getElementById('voidModal')) return;
      const openModal = (id) => {
        const el = document.getElementById(id);
        if (window.FcModal?.open) { window.FcModal.open(id); } else { el.classList.add('open'); }
      };
      const closeModal = (id) => {
        const el = document.getElementById(id);
        if (window.FcModal?.closeAll) { window.FcModal.closeAll(); } else { el.classList.remove('open'); }
      };

      document.querySelectorAll('.txn-link').forEach((a) => {
        a.addEventListener('click', async (e) => {
          e.preventDefault();
          const body = document.getElementById('txnModalBody');
          openModal('txnModal');
          body.innerHTML = '<div class="empty-state"><i data-lucide="loader"></i>Loading…</div>';
          try {
            const res = await fetch(a.dataset.txn, { headers: { 'Accept': 'application/json' } });
            const t = await res.json();
            const fmt = (v) => Number(v || 0).toLocaleString('en', { maximumFractionDigits: 2 });
            body.innerHTML = `
              <div class="detail-list">
                <div class="detail-item"><div class="dt">Receipt</div><div class="dd mono">${t.transaction_number}</div></div>
                <div class="detail-item"><div class="dt">Station</div><div class="dd">${t.station?.name ?? ''}</div></div>
                <div class="detail-item"><div class="dt">Fuel</div><div class="dd">${t.fuel_product?.name ?? ''} @ {{ currency() }} ${fmt(t.unit_price)}/L</div></div>
                <div class="detail-item"><div class="dt">Litres</div><div class="dd">${fmt(t.litres)} L</div></div>
                <div class="detail-item"><div class="dt">Amount</div><div class="dd">${fmt(t.gross_amount)}</div></div>
                <div class="detail-item"><div class="dt">Tax</div><div class="dd">${fmt(t.tax_amount)}</div></div>
                <div class="detail-item"><div class="dt">Discount</div><div class="dd">${fmt(t.discount_amount)}</div></div>
                <div class="detail-item"><div class="dt">Total</div><div class="dd bold">${fmt(t.net_amount)}</div></div>
                <div class="detail-item"><div class="dt">Payment</div><div class="dd">${t.payment?.method ?? 'cash'} ${t.payment?.reference ?? ''}</div></div>
                <div class="detail-item"><div class="dt">Attendant</div><div class="dd">${t.attendant?.name ?? '-'}</div></div>
                <div class="detail-item"><div class="dt">Time</div><div class="dd">${t.transacted_at ? new Date(t.transacted_at).toLocaleString() : ''}</div></div>
              </div>`;
          } catch (err) {
            body.innerHTML = '<div class="empty-state">Could not load transaction.</div>';
          }
        });
      });

      document.querySelectorAll('.void-link').forEach((a) => {
        a.addEventListener('click', (e) => {
          e.preventDefault();
          document.getElementById('voidForm').action = a.dataset.void;
          openModal('voidModal');
        });
      });

      document.querySelectorAll('.modal-close').forEach((b) => b.addEventListener('click', () => closeModal(b.closest('.modal-overlay').id)));
      document.querySelectorAll('.modal-overlay').forEach((o) => o.addEventListener('click', (e) => { if (e.target === o) closeModal(o.id); }));
      };
      document.addEventListener('turbo:load', window[KEY]);
    })();
  </script>
@endpush