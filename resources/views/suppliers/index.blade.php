@extends('layouts.app')

@section('active', 'suppliers')
@section('page_title', 'Suppliers')
@section('page_breadcrumb', 'FUELCORE / Suppliers')

@section('content')
  <div class="page-head">
    <div><h1>Suppliers</h1><p>Fuel and lubricant suppliers.</p></div>
    <div class="page-actions">
      @can('supplier.manage')
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add Supplier</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('suppliers.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search supplier…" style="flex:1;min-width:220px;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['active', 'inactive'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('suppliers.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Code</th><th>Supplier</th><th>Contact Person</th><th>Phone</th><th class="numeric">Deliveries</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($suppliers as $s)
              <tr>
                <td class="mono">{{ $s->code }}</td>
                <td class="bold">{{ $s->name }}</td>
                <td class="muted small">{{ $s->contact_person ?? '—' }}</td>
                <td class="muted small">{{ $s->phone ?? '—' }}</td>
                <td class="numeric">{{ $s->deliveries_count }}</td>
                <td><span class="status-pill-sm {{ $s->status === 'active' ? 'online' : 'warning' }}">{{ $s->status }}</span></td>
                <td class="center">
                  <div class="action-links">
                    @can('supplier.manage')
                      <a href="{{ route('suppliers.edit', $s) }}" title="Edit"><i data-lucide="pencil"></i></a>
                      @include('partials.toggle-status', [
                        'action' => route('suppliers.toggleStatus', $s),
                        'active' => $s->status === 'active',
                        'label' => $s->name,
                        'noun' => 'Supplier',
                      ])
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="7"><div class="empty-state">No suppliers found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $suppliers->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection