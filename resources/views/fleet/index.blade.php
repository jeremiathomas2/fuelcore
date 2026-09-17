@extends('layouts.app')

@section('active', 'fleet')
@section('page_title', 'Fleet Accounts')
@section('page_breadcrumb', 'FUELCORE / Fleet')

@section('content')
  <div class="page-head">
    <div><h1>Fleet Accounts</h1><p>Company fuel accounts with vehicles and credit limits.</p></div>
    <div class="page-actions">
      @can('fleet.manage')
        <a href="{{ route('fleet.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add Fleet Account</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('fleet.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search company or account no…" style="flex:1;min-width:220px;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['active', 'inactive', 'blocked'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('fleet.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Account No</th><th>Company</th><th>Customer</th><th>Contact</th><th class="numeric">Vehicles</th><th class="numeric">Credit Limit</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($accounts as $a)
              <tr onclick="window.location='{{ route('fleet.show', $a) }}'">
                <td class="mono">{{ $a->account_number }}</td>
                <td class="bold">{{ $a->company_name }}</td>
                <td class="muted small">{{ $a->customer?->name }}</td>
                <td class="muted small">{{ $a->contact_person ?? '—' }}<div>{{ $a->phone ?? '' }}</div></td>
                <td class="numeric">{{ $a->vehicles_count }}</td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float) $a->credit_limit, 0) }}</td>
                <td><span class="status-pill-sm {{ $a->status === 'active' ? 'online' : 'warning' }}">{{ $a->status }}</span></td>
                <td class="center">
                  <div class="action-links">
                    <a href="{{ route('fleet.show', $a) }}" title="View"><i data-lucide="eye"></i></a>
                    @can('fleet.manage')
                      <a href="{{ route('fleet.edit', $a) }}" title="Edit"><i data-lucide="pencil"></i></a>
                      @include('partials.toggle-status', [
                        'action' => route('fleet.toggleStatus', $a),
                        'active' => $a->status === 'active',
                        'label' => $a->company_name,
                        'noun' => 'Fleet account',
                      ])
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="8"><div class="empty-state">No fleet accounts found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $accounts->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection