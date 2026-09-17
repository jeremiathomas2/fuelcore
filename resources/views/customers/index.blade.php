@extends('layouts.app')

@section('active', 'customers')
@section('page_title', 'Customers')
@section('page_breadcrumb', 'FUELCORE / Customers')

@section('content')
  <div class="page-head">
    <div><h1>Customers</h1><p>Individuals, corporations and government fuel buyers.</p></div>
    <div class="page-actions">
      @can('customer.manage')
        <a href="{{ route('customers.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add Customer</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('customers.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search name, phone, number…" style="flex:1;min-width:200px;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <select name="type" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Types</option>
        @foreach (['individual', 'corporate', 'fleet', 'government', 'other'] as $t)
          <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
        @endforeach
      </select>
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['active', 'inactive', 'blocked'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="filter"></i> Filter</button>
      <a href="{{ route('customers.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Code</th><th>Customer</th><th>Type</th><th>Phone</th><th class="numeric">Transactions</th><th class="numeric">Credit Limit</th><th class="numeric">Outstanding</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($customers as $c)
              <tr onclick="window.location='{{ route('customers.show', $c) }}'">
                <td class="mono">{{ $c->customer_number }}</td>
                <td class="bold">{{ $c->name }}</td>
                <td><span class="badge badge-info">{{ ucfirst($c->type) }}</span></td>
                <td class="muted small">{{ $c->phone ?? '—' }}</td>
                <td class="numeric">{{ $c->transactions_count }}</td>
                <td class="numeric">{{ $c->credit_limit ? currency().' '.number_format((float) $c->credit_limit, 0) : '—' }}</td>
                <td class="numeric {{ (float) $c->outstanding_balance > 0 ? 'text-danger' : '' }}">{{ $c->outstanding_balance ? currency().' '.number_format((float) $c->outstanding_balance, 0) : '—' }}</td>
                <td><span class="status-pill-sm {{ $c->status === 'active' ? 'online' : 'warning' }}">{{ $c->status }}</span></td>
                <td class="center">
                  <div class="action-links">
                    <a href="{{ route('customers.show', $c) }}" class="icon-btn" title="View customer" onclick="event.stopPropagation()"><i data-lucide="eye"></i></a>
                    @can('customer.manage')
                      <a href="{{ route('customers.edit', $c) }}" class="icon-btn" title="Edit customer" onclick="event.stopPropagation()"><i data-lucide="square-pen"></i></a>
                      @include('partials.toggle-status', [
                        'action' => route('customers.toggleStatus', $c),
                        'active' => $c->status === 'active',
                        'label' => $c->name,
                        'noun' => 'Customer',
                      ])
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No customers found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $customers->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection