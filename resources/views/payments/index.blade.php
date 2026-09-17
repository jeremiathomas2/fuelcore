@extends('layouts.app')

@section('active', 'payments')
@section('page_title', 'Payments')
@section('page_breadcrumb', 'FUELCORE / Payments')

@section('content')
  <div class="page-head">
    <div><h1>Payments</h1><p>Record and track customer payments.</p></div>
    <div class="page-actions">
      @can('payment.manage')
        <a href="{{ route('payments.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Record Payment</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px;">
    @foreach ($totals as $method => $total)
      <div class="kpi-card">
        <div class="kpi-header"><div class="kpi-icon green"><i data-lucide="banknote"></i></div></div>
        <div class="kpi-value">{{ currency() }} {{ number_format((float) $total, 0) }}</div>
        <div class="kpi-label">{{ ucfirst(str_replace('_', ' ', $method)) }}</div>
      </div>
    @endforeach
    @if ($totals->isEmpty())
      <div class="kpi-card">
        <div class="kpi-header"><div class="kpi-icon green"><i data-lucide="banknote"></i></div></div>
        <div class="kpi-value">{{ currency() }} 0</div>
        <div class="kpi-label">All Methods</div>
      </div>
    @endif
  </section>

  <section class="filter-bar">
    <form action="{{ route('payments.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="method" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Methods</option>
        @foreach (['cash', 'mobile_money', 'card', 'fleet_account', 'credit'] as $m)
          <option value="{{ $m }}" {{ request('method') === $m ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
        @endforeach
      </select>
      <input type="date" name="from" value="{{ request('from') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <input type="date" name="to" value="{{ request('to') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('payments.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Payment No</th><th>Receipt</th><th>Station</th><th>Fuel</th><th class="numeric">Amount</th><th>Method</th><th>Reference</th><th>By</th><th>Time</th></tr></thead>
          <tbody>
            @forelse ($payments as $p)
              <tr>
                <td class="mono">{{ $p->payment_number }}</td>
                <td class="muted small">{{ $p->transaction?->transaction_number ?? '—' }}</td>
                <td>{{ $p->transaction?->station?->name ?? '—' }}</td>
                <td>{{ $p->transaction?->fuelProduct?->name ?? '—' }}</td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float) $p->amount, 0) }}</td>
                <td><span class="badge badge-{{ $p->method === 'cash' ? 'success' : 'info' }}">{{ ucfirst(str_replace('_', ' ', $p->method)) }}</span></td>
                <td class="muted small">{{ $p->reference ?? '—' }}</td>
                <td class="muted small">{{ $p->createdBy?->name }}</td>
                <td class="muted small">{{ $p->created_at->format('d M Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No payments recorded.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $payments->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection