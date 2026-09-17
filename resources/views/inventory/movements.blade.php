@extends('layouts.app')

@section('active', 'inventory')
@section('page_title', 'Inventory Movements')
@section('page_breadcrumb', 'FUELCORE / Inventory / Movements')

@section('content')
  <div class="page-head">
    <div><h1>Inventory Movements</h1><p>Stock receipts, issues and adjustments.</p></div>
    <div class="page-actions"><a href="{{ route('inventory.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('inventory.movements') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="type" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Types</option>
        @foreach (['receive', 'sale', 'adjustment', 'transfer', 'return', 'loss'] as $t)
          <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
        @endforeach
      </select>
      <input type="date" name="from" value="{{ request('from') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <input type="date" name="to" value="{{ request('to') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <button class="btn-sm primary" type="submit"><i data-lucide="filter"></i> Filter</button>
      <a href="{{ route('inventory.movements') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Date</th><th>Station</th><th>Fuel</th><th>Type</th><th class="numeric">Qty</th><th>Note</th><th>By</th></tr></thead>
          <tbody>
            @forelse ($movements as $m)
              <tr>
                <td class="muted small">{{ $m->created_at->format('d M Y H:i') }}</td>
                <td>{{ $m->station?->name }}</td>
                <td><span class="fuel-badge petrol">{{ $m->product?->name }}</span></td>
                <td>
                  @php($tone = ['receive' => 'online', 'sale' => 'neutral', 'adjustment' => 'warning', 'transfer' => 'info', 'return' => 'online', 'loss' => 'error'][$m->type] ?? 'neutral')
                  <span class="status-pill-sm {{ $tone }}">{{ $m->type }}</span>
                </td>
                <td class="numeric bold {{ (float) $m->quantity >= 0 ? 'text-success' : 'text-danger' }}">{{ $m->quantity >= 0 ? '+' : '' }}{{ number_format((float) $m->quantity, 0) }} L</td>
                <td class="small muted">{{ $m->note }}</td>
                <td class="muted small">{{ $m->createdBy?->name ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="7"><div class="empty-state">No movements found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $movements->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection